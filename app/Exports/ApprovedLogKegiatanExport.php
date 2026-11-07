<?php

namespace App\Exports;

use App\Models\LogKegiatan;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApprovedLogKegiatanExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private Builder $logs) {}

    public function query(): Builder
    {
        return $this->logs;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID Log',
            'Tanggal Kehadiran',
            'Sesi Kehadiran',
            'Nama Mahasiswa',
            'NRP',
            'Email',
            'Program Studi',
            'Kode Kegiatan',
            'Nama Kegiatan',
            'Lokasi',
            'Deskripsi Kegiatan',
            'Deskripsi Luaran',
            'Status',
        ];
    }

    /**
     * @return array<int, int|string>
     */
    public function map(mixed $log): array
    {
        if (! $log instanceof LogKegiatan) {
            return [];
        }

        return [
            $log->id,
            $log->tanggal_kehadiran?->format('Y-m-d') ?? '',
            $this->safeCell($log->jam_kehadiran),
            $this->safeCell($log->peserta?->nama_lengkap),
            $this->safeCell($log->peserta?->nim),
            $this->safeCell($log->peserta?->user?->email),
            $this->safeCell($log->peserta?->prodi?->nama_prodi ?? $log->peserta?->program_studi),
            $this->safeCell($log->masterKegiatan?->kode_kegiatan),
            $this->safeCell($log->masterKegiatan?->nama_kegiatan),
            $this->safeCell($log->lokasi_kegiatan),
            $this->safeCell($log->deskripsi_kegiatan),
            $this->safeCell($log->deskripsi_luaran),
            $log->status,
        ];
    }

    private function safeCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\t\r\n=+\-@]/', $value) === 1
            ? "'{$value}"
            : $value;
    }
}
