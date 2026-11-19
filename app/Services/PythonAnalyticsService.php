<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PythonAnalyticsService
{
    /**
     * Resolve the Python binary path, verifying reportlab is available.
     *
     * Falls back through configured binary → common system paths until one
     * is found that can import reportlab.  The result is cached for the
     * lifetime of the request.
     */
    private function resolvePythonBinary(): string
    {
        static $resolved = null;

        if ($resolved !== null) {
            return $resolved;
        }

        $configured = config('services.python.binary', 'python');

        // Candidates: configured value first, then common Windows/Laragon paths.
        $candidates = array_unique(array_filter([
            $configured,
            base_path('.venv/Scripts/python.exe'),
            base_path('.venv/bin/python'),
            'C:/laragon/bin/python/python-3.13/python.exe',
            'python3',
            'python',
        ]));

        foreach ($candidates as $binary) {
            if (! $this->binaryExists($binary)) {
                continue;
            }

            $check = new Process([$binary, '-c', 'import reportlab; print("ok")']);
            $check->setTimeout(10);
            $check->run();

            if ($check->isSuccessful() && str_contains($check->getOutput(), 'ok')) {
                $resolved = $binary;

                return $resolved;
            }
        }

        // Last resort: use the configured value and let the caller handle the error.
        $resolved = $configured;

        return $resolved;
    }

    private function binaryExists(string $binary): bool
    {
        // Absolute or relative path that exists on disk.
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return file_exists($binary);
        }

        // Bare command name – rely on Process to resolve via PATH.
        return true;
    }

    /**
     * Run a supported Python analysis and return its JSON result.
     *
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    public function run(string $operation, array $payload): array
    {
        if (! in_array($operation, ['rekap-jam', 'analisis-progres'], true)) {
            throw new RuntimeException('Operasi Python tidak dikenal.');
        }

        $process = new Process([
            $this->resolvePythonBinary(),
            base_path('rekap_jam_kegiatan.py'),
            $operation,
        ]);

        try {
            $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput());
            }

            $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($result)) {
                throw new RuntimeException('Hasil analisis Python tidak valid.');
            }

            return $result;
        } catch (Throwable $exception) {
            throw new RuntimeException('Analisis Python gagal dijalankan.', previous: $exception);
        }
    }

    /**
     * Generate a student activity report as a PDF document.
     *
     * @param  array<string, mixed>  $payload
     */
    public function generateStudentActivityPdf(array $payload): string
    {
        $process = new Process([
            $this->resolvePythonBinary(),
            base_path('laporan_kegiatan_mahasiswa.py'),
        ]);

        try {
            $process->setInput(json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            ));

            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException($process->getErrorOutput());
            }

            $pdf = $process->getOutput();

            if (
                ! str_starts_with($pdf, '%PDF-')
                || ! str_ends_with(rtrim($pdf), '%%EOF')
            ) {
                throw new RuntimeException('Output Python bukan dokumen PDF yang valid.');
            }

            return $pdf;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Pembuatan laporan PDF Python gagal dijalankan.',
                previous: $exception
            );
        }
    }
}
