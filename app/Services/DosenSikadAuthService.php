<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DosenSikadAuthService
{
    public function authenticate(
        string $username,
        string $password
    ): ?array {
        $endpoint = config('services.campus_auth.dosen_endpoint');
        $apiKey = config('services.campus_auth.api_key');

        if (! filled($endpoint) || ! filled($apiKey)) {
            throw new RuntimeException(
                'Konfigurasi API autentikasi dosen belum lengkap.'
            );
        }

        $response = Http::acceptJson()
            ->timeout(max(
                1,
                (int) config('services.campus_auth.timeout', 10)
            ))
            ->post($endpoint, [
                'APIKEY' => $apiKey,
                'username' => $username,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (
            ! is_array($payload) ||
            ($payload['success'] ?? false) !== true
        ) {
            return null;
        }

        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        $lecturer = array_is_list($data)
            ? ($data[0] ?? null)
            : $data;

        if (! is_array($lecturer)) {
            return null;
        }

        $nomorDosen = trim(
            (string) ($lecturer['nomor_dosen'] ?? '')
        );

        $nama = trim(
            (string) ($lecturer['nama'] ?? '')
        );

        if ($nomorDosen === '' || $nama === '') {
            return null;
        }

        return [
            'nomor_dosen' => $nomorDosen,
            'nama' => $nama,
            'kode_fakultas_sikad' => $this->clean(
                $lecturer['kode_fakultas'] ?? null
            ),
            'kode_prodi_sikad' => $this->clean(
                $lecturer['kode_prodi'] ?? null
            ),
            'nidn' => $this->clean(
                $lecturer['nidn'] ?? null
            ),
            'gelar_depan' => $this->clean(
                $lecturer['gelar_depan'] ?? null
            ),
            'gelar_belakang' => $this->clean(
                $lecturer['gelar_belakang'] ?? null
            ),
            'status_sikad' => $this->clean(
                $lecturer['status'] ?? null
            ),
        ];
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}