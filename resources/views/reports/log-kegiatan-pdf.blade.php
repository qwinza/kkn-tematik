<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Kegiatan Mahasiswa</title>
    <style>
        @page { margin: 24px 28px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #123b61; font-size: 18px; margin: 0 0 4px; }
        .subtitle { color: #64748b; font-size: 10px; margin-bottom: 16px; }
        .meta { color: #475569; font-size: 9px; margin-bottom: 14px; }
        .summary { border-collapse: collapse; margin-bottom: 16px; width: 100%; }
        .summary td { background: #f1f5f9; border: 1px solid #dbe3ec; padding: 7px 9px; width: 20%; }
        .summary strong { color: #123b61; display: block; font-size: 12px; margin-top: 3px; }
        table.report { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .report th { background: #123b61; color: #fff; font-size: 8px; padding: 7px 5px; text-align: left; }
        .report td { border: 1px solid #dbe3ec; font-size: 8px; padding: 6px 5px; vertical-align: top; word-wrap: break-word; }
        .report tr { page-break-inside: avoid; }
        .muted { color: #64748b; }
        .status { font-weight: bold; text-transform: capitalize; }
        .empty { color: #64748b; padding: 18px 6px !important; text-align: center; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 8px; margin-top: 18px; padding-top: 7px; }
    </style>
</head>
<body>
    <h1>Laporan Riwayat Kegiatan Mahasiswa</h1>
    <div class="subtitle">Program Kuliah Kerja Nyata ITENAS RESIK 2026</div>
    <div class="meta">
        @if ($isDpp)
            Program Studi: <strong>{{ $prodiName ?: '-' }}</strong> &nbsp;|&nbsp;
        @else
            Cakupan: <strong>Seluruh Program Studi</strong> &nbsp;|&nbsp;
        @endif
        Dibuat: {{ $generatedAt->format('d-m-Y H:i') }}
    </div>

    <table class="summary">
        <tr>
            <td>Total laporan<strong>{{ $summary['total'] }}</strong></td>
            <td>Disetujui<strong>{{ $summary['disetujui'] }}</strong></td>
            <td>Menunggu<strong>{{ $summary['menunggu'] }}</strong></td>
            <td>Ditolak<strong>{{ $summary['ditolak'] }}</strong></td>
            <td>Total durasi disetujui<strong>{{ number_format($summary['totalJamDisetujui'], 1, ',', '.') }} jam</strong></td>
        </tr>
    </table>

    <table class="report">
        <colgroup>
            <col style="width: 4%"><col style="width: 9%"><col style="width: 14%">
            <col style="width: 11%"><col style="width: 9%"><col style="width: 18%">
            <col style="width: 7%"><col style="width: 9%"><col style="width: 19%">
        </colgroup>
        <thead>
            <tr>
                <th>No.</th>
                <th>NIM</th>
                <th>Mahasiswa</th>
                <th>Program Studi</th>
                <th>Tanggal</th>
                <th>Kegiatan</th>
                <th>Durasi</th>
                <th>Status</th>
                <th>Rincian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $log->peserta?->nim ?: '-' }}</td>
                    <td>{{ $log->peserta?->nama_lengkap ?: '-' }}</td>
                    <td>{{ $log->peserta?->prodi?->nama_prodi ?: $log->peserta?->program_studi ?: '-' }}</td>
                    <td>{{ $log->tanggal_kehadiran?->format('d-m-Y') ?: '-' }}<br><span class="muted">{{ $log->jam_kehadiran ?: '' }}</span></td>
                    <td>{{ $log->masterKegiatan?->nama_kegiatan ?: 'Belum dipilih' }}<br><span class="muted">{{ $log->masterKegiatan?->kode_kegiatan ?: '' }}</span></td>
                    <td>{{ number_format($log->durasiJam(), 1, ',', '.') }} jam</td>
                    <td class="status">{{ $log->status }}</td>
                    <td>{{ $log->deskripsi_kegiatan ?: '-' }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="9">Belum ada riwayat kegiatan yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Laporan dibuat otomatis melalui Portal KKN ITENAS RESIK.</div>
</body>
</html>
