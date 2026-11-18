<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Kegiatan Mahasiswa</title>
    <style>
        @page { margin: 25px 28px 30px; }
        body { color: #243447; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.4; }
        .heading { border-collapse: collapse; margin-bottom: 14px; width: 100%; }
        .heading .accent { background: #159b8a; width: 6px; }
        .heading .title { border-bottom: 2px solid #dce6ef; padding: 2px 12px 10px; }
        .heading .meta { border-bottom: 2px solid #dce6ef; color: #53677c; padding: 2px 0 10px; text-align: right; vertical-align: top; width: 35%; }
        .eyebrow { color: #159b8a; font-size: 7px; font-weight: bold; letter-spacing: 1px; margin-bottom: 3px; }
        h1 { color: #123b61; font-size: 17px; margin: 0 0 3px; }
        .subtitle { color: #64748b; font-size: 9px; }
        .role-badge { background: #e8f3f8; color: #123b61; font-size: 7px; font-weight: bold; padding: 5px 8px; }
        .meta-detail { margin-top: 8px; }
        .meta-detail strong { color: #243447; }
        .summary { border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; width: 100%; }
        .summary td { background: #f4f7fb; border: 1px solid #dce5ef; border-top: 3px solid #8da7bd; padding: 7px 8px; width: 20%; }
        .summary td.approved { border-top-color: #159b8a; }
        .summary td.pending { border-top-color: #e0a12f; }
        .summary td.rejected { border-top-color: #cf5b5b; }
        .summary td.hours { border-top-color: #456f9d; }
        .summary-label { color: #64748b; font-size: 6.5px; font-weight: bold; text-transform: uppercase; }
        .summary strong { color: #123b61; display: block; font-size: 13px; margin-top: 3px; }
        table.report { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .report thead { display: table-header-group; }
        .report th { background: #123b61; border: 1px solid #123b61; color: #fff; font-size: 7px; padding: 7px 4px; text-align: left; }
        .report td { border: 1px solid #dce5ef; font-size: 7px; padding: 6px 4px; vertical-align: top; word-wrap: break-word; }
        .report tbody tr:nth-child(even) td { background: #f7f9fc; }
        .report tr { page-break-inside: avoid; }
        .report .number, .report .hours { color: #123b61; font-weight: bold; }
        .report .student-name { color: #243447; font-weight: bold; }
        .muted { color: #64748b; font-size: 6.5px; }
        .status { font-weight: bold; }
        .status-disetujui { color: #13745f; }
        .status-menunggu { color: #9a650d; }
        .status-ditolak { color: #a83e3e; }
        .empty { background: #f7f9fc; color: #64748b; padding: 22px 6px !important; text-align: center; }
        .empty strong { color: #334e68; display: block; font-size: 9px; margin-bottom: 3px; }
        .footer { border-top: 1px solid #cbd5e1; color: #64748b; font-size: 7px; margin-top: 14px; padding-top: 7px; }
    </style>
</head>
<body>
    <table class="heading">
        <tr>
            <td class="accent"></td>
            <td class="title">
                <div class="eyebrow">PORTAL KKN ITENAS RESIK 2026</div>
                <h1>Laporan Riwayat Kegiatan Mahasiswa</h1>
                <div class="subtitle">Rekap aktivitas dan persetujuan kegiatan mahasiswa</div>
            </td>
            <td class="meta">
                <span class="role-badge">{{ $isDpp ? 'LAPORAN DPP' : 'LAPORAN ADMIN' }}</span>
                <div class="meta-detail">
                    @if ($isDpp)
                        Program Studi<br><strong>{{ $prodiName ?: '-' }}</strong>
                    @else
                        Cakupan<br><strong>Seluruh Program Studi</strong>
                    @endif
                </div>
                <div class="meta-detail">Dibuat: <strong>{{ $generatedAt->format('d-m-Y H:i') }}</strong></div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><div class="summary-label">Total Laporan</div><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong></td>
            <td class="approved"><div class="summary-label">Disetujui</div><strong>{{ number_format($summary['disetujui'], 0, ',', '.') }}</strong></td>
            <td class="pending"><div class="summary-label">Menunggu</div><strong>{{ number_format($summary['menunggu'], 0, ',', '.') }}</strong></td>
            <td class="rejected"><div class="summary-label">Ditolak</div><strong>{{ number_format($summary['ditolak'], 0, ',', '.') }}</strong></td>
            <td class="hours"><div class="summary-label">Total Jam Disetujui</div><strong>{{ number_format($summary['totalJamDisetujui'], 1, ',', '.') }} jam</strong></td>
        </tr>
    </table>

    <table class="report">
        <colgroup>
            <col style="width: 3%"><col style="width: 8%"><col style="width: 12%">
            <col style="width: 10%"><col style="width: 8%"><col style="width: 14%">
            <col style="width: 6%"><col style="width: 8%"><col style="width: 9%">
            <col style="width: 22%">
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
                <th>Total Jam Disetujui</th>
                <th>Status</th>
                <th>Rincian</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $index => $log)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td>{{ $log->peserta?->nim ?: '-' }}</td>
                    <td class="student-name">{{ $log->peserta?->nama_lengkap ?: '-' }}</td>
                    <td>{{ $log->peserta?->prodi?->nama_prodi ?: $log->peserta?->program_studi ?: '-' }}</td>
                    <td>{{ $log->tanggal_kehadiran?->format('d-m-Y') ?: '-' }}<br><span class="muted">{{ $log->jam_kehadiran ?: '' }}</span></td>
                    <td>{{ $log->masterKegiatan?->nama_kegiatan ?: 'Belum dipilih' }}<br><span class="muted">{{ $log->masterKegiatan?->kode_kegiatan ?: '' }}</span></td>
                    <td>{{ number_format($log->durasiJam(), 1, ',', '.') }} jam</td>
                    <td class="hours">{{ number_format($approvedHoursByStudent->get($log->peserta_id, 0), 1, ',', '.') }} jam</td>
                    <td class="status status-{{ $log->status }}">{{ ucfirst($log->status) }}</td>
                    <td>{{ $log->deskripsi_kegiatan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="10">
                        <strong>Belum ada data kegiatan</strong>
                        Tidak ada riwayat kegiatan yang sesuai dengan filter laporan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Total jam per mahasiswa hanya menghitung kegiatan berstatus disetujui yang sesuai dengan filter laporan.
        Laporan dibuat otomatis melalui Portal KKN ITENAS RESIK.
    </div>
</body>
</html>
