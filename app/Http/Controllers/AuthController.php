<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman form login.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle proses login dengan pemisahan autentikasi Admin dan Mahasiswa.
     *
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required_without:email', 'string'],
            'email' => ['required_without:identifier', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim((string) ($request->input('identifier') ?? $request->input('email')));
        $password = (string) $request->input('password');

        $this->ensureIsNotRateLimited($identifier, $request->ip());

        // 1. Cek apakah input adalah email. Jika ya, autentikasi sebagai Admin (Database Lokal).
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->handleAdminLogin($request, $identifier, $password);
        }

        // 2. Jika input berupa angka/NIM, lakukan simulasi hit API kampus ITENAS.
        if (preg_match('/^[0-9]+$/', $identifier)) {
            return $this->handleMahasiswaLogin($request, $identifier, $password);
        }

        $this->hitRateLimiter($identifier, $request->ip());

        throw ValidationException::withMessages([
            'identifier' => 'Format login tidak valid. Masukkan Email untuk Admin atau NIM untuk Mahasiswa.',
            'email' => 'Format login tidak valid. Masukkan Email untuk Admin atau NIM untuk Mahasiswa.',
        ]);
    }

    /**
     * Alias method store untuk kompatibilitas route Breeze.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->login($request);
    }

    /**
     * Logika autentikasi Admin menggunakan database lokal.
     *
     * @throws ValidationException
     */
    protected function handleAdminLogin(Request $request, string $email, string $password): RedirectResponse
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password], $request->boolean('remember'))) {
            $this->hitRateLimiter($email, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin()) {
            Auth::logout();
            $this->hitRateLimiter($email, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Akses ditolak. Email hanya diperuntukkan untuk akun Admin.',
                'email' => 'Akses ditolak. Email hanya diperuntukkan untuk akun Admin.',
            ]);
        }

        $this->clearRateLimiter($email, $request->ip());
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Logika autentikasi Mahasiswa via simulasi API eksternal kampus.
     *
     * @throws ValidationException
     */
    protected function handleMahasiswaLogin(Request $request, string $nim, string $password): RedirectResponse
    {
        // Simulasi hit API eksternal ITENAS
        $apiResponse = $this->simulateCampusApiLogin($nim, $password);

        if (! $apiResponse['success']) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => $apiResponse['message'] ?? 'NIM atau password akun kampus salah.',
                'email' => $apiResponse['message'] ?? 'NIM atau password akun kampus salah.',
            ]);
        }

        $studentData = $apiResponse['data'];

        // Cek apakah user dengan NIM tersebut sudah ada di tabel users
        $user = User::where('nim', $nim)->first();

        if (! $user) {
            // Buatkan shadow user di tabel users dan data profil di tabel pesertas
            $user = DB::transaction(function () use ($nim, $password, $studentData) {
                $newUser = User::create([
                    'name' => $studentData['nama_lengkap'],
                    'nim' => $nim,
                    'email' => null,
                    'password' => Hash::make($password),
                    'role' => 'mahasiswa',
                ]);

                Peserta::create([
                    'user_id' => $newUser->id,
                    'nama_lengkap' => $studentData['nama_lengkap'],
                    'nim' => $nim,
                    'program_studi' => $studentData['program_studi'],
                ]);

                return $newUser;
            });
        } else {
            // Sinkronkan password lokal jika password kampus diperbarui
            $user->update([
                'password' => Hash::make($password),
            ]);

            // Pastikan relasi profil peserta sudah terbuat
            if (! $user->peserta) {
                Peserta::create([
                    'user_id' => $user->id,
                    'nama_lengkap' => $studentData['nama_lengkap'] ?? $user->name,
                    'nim' => $nim,
                    'program_studi' => $studentData['program_studi'] ?? 'Teknik Informatika',
                ]);
            }
        }

        $this->clearRateLimiter($nim, $request->ip());

        // Login-kan user mahasiswa
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Simulasi hit API eksternal kampus ITENAS.
     *
     * @return array{success: bool, message?: string, data?: array{nim: string, nama_lengkap: string, program_studi: string}}
     */
    protected function simulateCampusApiLogin(string $nim, string $password): array
    {
        // TODO: Ganti blok ini dengan hit API eksternal ITENAS nantinya menggunakan Http::post()
        // Contoh implementasi di masa depan:
        // $response = Http::post('https://api.itenas.ac.id/auth/student/login', [
        //     'nim' => $nim,
        //     'password' => $password,
        // ]);
        // if ($response->successful()) {
        //     return [
        //         'success' => true,
        //         'data' => $response->json(),
        //     ];
        // }

        // Dummy Logic: Jika NIM == '152024038' dan password == 'password123', maka valid.
        if ($nim === '152024038' && $password === 'password123') {
            return [
                'success' => true,
                'data' => [
                    'nim' => '152024038',
                    'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
                    'program_studi' => 'Teknik Informatika',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'NIM atau password akun kampus ITENAS tidak valid.',
        ];
    }

    /**
     * Logout user dan hancurkan session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Memastikan request belum melebihi batas rate limiter.
     *
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(string $identifier, ?string $ip): void
    {
        $throttleKey = $this->throttleKey($identifier, $ip);

        if (! RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'identifier' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Hit rate limiter pada saat autentikasi gagal.
     */
    protected function hitRateLimiter(string $identifier, ?string $ip): void
    {
        RateLimiter::hit($this->throttleKey($identifier, $ip));
    }

    /**
     * Bersihkan rate limiter pada saat autentikasi berhasil.
     */
    protected function clearRateLimiter(string $identifier, ?string $ip): void
    {
        RateLimiter::clear($this->throttleKey($identifier, $ip));
    }

    /**
     * Generate key untuk throttle rate limiting.
     */
    protected function throttleKey(string $identifier, ?string $ip): string
    {
        return Str::transliterate(Str::lower($identifier).'|'.$ip);
    }
}
