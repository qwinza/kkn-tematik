<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
     * Logika autentikasi Mahasiswa via database lokal atau simulasi API ITENAS.
     *
     * @throws ValidationException
     */
    protected function handleMahasiswaLogin(Request $request, string $nim, string $password): RedirectResponse
    {
        $user = User::where('nim', $nim)
            ->where('role', 'mahasiswa')
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
                'email' => trans('auth.failed'),
            ]);
        }

        if (! $user->peserta) {
            Peserta::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'nim' => $nim,
            ]);
        } else {
            $this->syncProdi($user->peserta);
        }

        $this->clearRateLimiter($nim, $request->ip());
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.first-login.edit');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    protected function syncProdi(Peserta $peserta, ?string $programStudi = null): void
    {
        if ($peserta->kode_prodi) {
            return;
        }

        $programStudiName = trim($programStudi ?? (string) $peserta->program_studi);

        if ($programStudiName === '') {
            return;
        }

        $matchedProdi = Prodi::where('nama_prodi', $programStudiName)->first();

        if ($matchedProdi) {
            $peserta->update([
                'kode_prodi' => $matchedProdi->kode_prodi,
            ]);
        }
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
