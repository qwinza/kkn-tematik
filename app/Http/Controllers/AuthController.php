<?php

namespace App\Http\Controllers;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use App\Models\DppAccessRequest;
use App\Services\DosenSikadAuthService;
use RuntimeException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Menangani autentikasi Admin, Mahasiswa, dan Dosen.
     *
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'identifier' => ['required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim(
            (string) ($request->input('identifier') ?? $request->input('email'))
        );

        $password = (string) $request->input('password');

        $this->ensureIsNotRateLimited($identifier, $request->ip());

        // Email hanya diarahkan ke Admin jika terdaftar sebagai akun Admin lokal.
        $isAdminAccount = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            && User::query()
                ->where('email', $identifier)
                ->where('role', 'admin')
                ->exists();

        if ($isAdminAccount) {
            return $this->handleStaffLogin(
                $request,
                $identifier,
                $password
            );
        }

        // Prioritaskan NIM yang terdaftar sebagai mahasiswa.
        $isRegisteredMahasiswa = preg_match('/^[0-9]+$/', $identifier) === 1
            && Peserta::query()
                ->where('nim', $identifier)
                ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
                ->exists();

        if ($isRegisteredMahasiswa) {
            return $this->handleMahasiswaLogin(
                $request,
                $identifier,
                $password
            );
        }

        // Identitas lainnya, termasuk username dosen numerik,
        // diverifikasi melalui API SIKAD.

        // Identitas lainnya diverifikasi melalui API SIKAD Dosen.
        return $this->handleDosenLogin(
            $request,
            $identifier,
            $password
        );
    }

    /**
     * Alias method store untuk kompatibilitas route Breeze.
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->login($request);
    }

    /**
     * Autentikasi Admin menggunakan database lokal.
     *
     * @throws ValidationException
     */
    protected function handleStaffLogin(
        Request $request,
        string $email,
        string $password
    ): RedirectResponse {
        if (! Auth::attempt(
            ['email' => $email, 'password' => $password],
            $request->boolean('remember')
        )) {
            $this->hitRateLimiter($email, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin()) {
            Auth::logout();

            $this->hitRateLimiter($email, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Akun ini tidak memiliki akses login Admin.',
            ]);
        }

        $this->clearRateLimiter($email, $request->ip());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Autentikasi dosen melalui API SIKAD dan proses persetujuan Admin.
     *
     * @throws ValidationException
     */
    protected function handleDosenLogin(
        Request $request,
        string $username,
        string $password
    ): RedirectResponse {
        try {
            $identity = app(DosenSikadAuthService::class)
                ->authenticate($username, $password);
        } catch (ConnectionException) {
            $this->hitRateLimiter($username, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Layanan SIKAD tidak dapat dihubungi. Silakan coba lagi.',
            ]);
        } catch (RuntimeException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'identifier' => 'Konfigurasi autentikasi dosen belum tersedia.',
            ]);
        }

        if (! $identity) {
            $this->hitRateLimiter($username, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Username atau password SIKAD tidak valid.',
            ]);
        }

        $nomorDosen = $identity['nomor_dosen'];

        $accessRequest = DppAccessRequest::query()
            ->where('nomor_dosen', $nomorDosen)
            ->first();

        $usernameOwner = DppAccessRequest::query()
            ->where('username_sikad', $username)
            ->first();

        // Mencegah username yang sama digunakan oleh identitas dosen berbeda.
        if (
            $usernameOwner &&
            $usernameOwner->nomor_dosen !== $nomorDosen
        ) {
            $this->hitRateLimiter($username, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Identitas akun tidak cocok. Hubungi Admin.',
            ]);
        }

        if (! $accessRequest) {
            $accessRequest = $usernameOwner;
        }

        $requestData = [
            'username_sikad' => $username,
            'nomor_dosen' => $nomorDosen,
            'nama' => $identity['nama'],
            'kode_fakultas_sikad' => $identity['kode_fakultas_sikad'],
            'kode_prodi_sikad' => $identity['kode_prodi_sikad'],
            'nidn' => $identity['nidn'],
            'gelar_depan' => $identity['gelar_depan'],
            'gelar_belakang' => $identity['gelar_belakang'],
            'status_sikad' => $identity['status_sikad'],
        ];

        if ($accessRequest) {
            $accessRequest->fill($requestData)->save();
        } else {
            $accessRequest = DppAccessRequest::create([
                ...$requestData,
                'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
            ]);
        }

        // Permintaan baru atau yang masih menunggu persetujuan.
        if ($accessRequest->isPending()) {
            $this->clearRateLimiter($username, $request->ip());

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Identitas dosen berhasil diverifikasi. Silakan tunggu persetujuan Admin sebelum dapat mengakses sistem.'
                );
        }

        // Permintaan yang sudah ditolak tidak boleh masuk dashboard.
        if ($accessRequest->isRejected()) {
            $this->clearRateLimiter($username, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Permintaan akses Anda ditolak. Silakan hubungi Admin.',
            ]);
        }

        // Hanya permintaan yang disetujui yang dapat masuk.
        if ($accessRequest->isApproved()) {
            $user = $accessRequest->user;

            if (
                ! $user ||
                ! $user->isDpp() ||
                ! $user->dppProfile()->exists()
            ) {
                throw ValidationException::withMessages([
                    'identifier' => 'Akun DPP belum dikonfigurasi dengan benar. Hubungi Admin.',
                ]);
            }

            $user->update([
                'name' => $identity['nama'],
            ]);

            $this->clearRateLimiter($username, $request->ip());

            Auth::login(
                $user,
                $request->boolean('remember')
            );

            $request->session()->regenerate();

            return redirect()->intended(
                route('dashboard', absolute: false)
            );
        }

        throw ValidationException::withMessages([
            'identifier' => 'Status permintaan akses tidak dikenali. Hubungi Admin.',
        ]);
    }

    /**
     * Autentikasi mahasiswa melalui API kampus untuk peserta KKN yang terdaftar.
     *
     * @throws ValidationException
     */
    protected function handleMahasiswaLogin(Request $request, string $nim, string $password): RedirectResponse
    {
        $peserta = Peserta::query()
            ->where('nim', $nim)
            ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
            ->with('user')
            ->first();

        if (! $peserta || ! $peserta->user) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
                'email' => trans('auth.failed'),
            ]);
        }

        // Fallback pengembangan: login mahasiswa pakai password lokal saat APP_ENV=local.
        if (app()->environment('local')) {
            if (Hash::check($password, $peserta->user->password)) {
                $this->clearRateLimiter($nim, $request->ip());
                Auth::login($peserta->user, $request->boolean('remember'));
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard', absolute: false));
            }
        }

        $endpoint = config('services.campus_auth.endpoint');
        $apiKey = config('services.campus_auth.api_key');

        if (! filled($endpoint) || ! filled($apiKey)) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Layanan login mahasiswa belum dikonfigurasi.',
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('services.campus_auth.timeout', 10)))
                ->post($endpoint, [
                    'APIKEY' => $apiKey,
                    'username' => $nim,
                    'password' => $password,
                ]);
        } catch (ConnectionException) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => 'Layanan kampus tidak dapat dihubungi. Silakan coba lagi.',
            ]);
        }

        $payload = $response->json();
        $apiStatus = is_array($payload) ? strtolower((string) ($payload['status'] ?? '')) : '';
        $isAuthenticated = $response->successful()
            && is_array($payload)
            && (($payload['success'] ?? false) === true
                || ($payload['status'] ?? false) === true
                || in_array($apiStatus, ['success', 'sukses', 'ok'], true));

        if (! $isAuthenticated) {
            $this->hitRateLimiter($nim, $request->ip());

            throw ValidationException::withMessages([
                'identifier' => trans('auth.failed'),
                'email' => trans('auth.failed'),
            ]);
        }

        $user = $peserta->user;
        $this->syncCampusProfile($user, $peserta, $payload);
        $this->clearRateLimiter($nim, $request->ip());
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Sinkronkan identitas yang diberikan API tanpa menimpa data lokal yang tidak tersedia di respons.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function syncCampusProfile(User $user, Peserta $peserta, array $payload): void
    {
        $profile = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $name = $profile['nama_lengkap'] ?? $profile['nama'] ?? $profile['name'] ?? null;
        $programStudi = $profile['program_studi'] ?? $profile['nama_prodi'] ?? $profile['prodi'] ?? null;

        if (is_array($programStudi)) {
            $programStudi = $programStudi['nama_prodi'] ?? $programStudi['nama'] ?? null;
        }

        if (is_string($name) && trim($name) !== '') {
            $name = trim($name);
            $user->update(['name' => $name]);
            $peserta->update(['nama_lengkap' => $name]);
        }

        if (is_string($programStudi) && trim($programStudi) !== '') {
            $peserta->update(['program_studi' => trim($programStudi)]);
            $this->syncProdi($peserta, trim($programStudi));
        } else {
            $this->syncProdi($peserta);
        }
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
