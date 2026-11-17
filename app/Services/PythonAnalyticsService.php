<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PythonAnalyticsService
{
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
            config('services.python.binary', 'python'),
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
            config('services.python.binary', 'python'),
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
