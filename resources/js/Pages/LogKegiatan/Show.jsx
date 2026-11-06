import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';

export default function LogKegiatanShow({ log, isAdmin }) {
    const [isApproving, setIsApproving] = useState(false);
    const [isRejecting, setIsRejecting] = useState(false);
    const hasCoords = log.latitude && log.longitude;
    const mapUrl = hasCoords ? `https://www.google.com/maps?q=${log.latitude},${log.longitude}` : null;
    const photoFiles = log.files?.filter((file) => file.jenis_file === 'foto_kegiatan') ?? [];
    const outputFiles = log.files?.filter((file) => file.jenis_file === 'file_luaran') ?? [];

    const handleApprove = async () => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Setujui laporan ini?',
            text: 'Status laporan akan berubah menjadi disetujui.',
            confirmButtonText: 'Ya, setujui',
            icon: 'question',
        });
        if (!confirmed) return;

        router.post(route('log-kegiatan.approve', log.route_key), {}, {
            preserveScroll: true,
            onStart: () => setIsApproving(true),
            onFinish: () => setIsApproving(false),
        });
    };

    const handleReject = async () => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Tolak laporan ini?',
            text: 'Mahasiswa perlu memperbaiki laporan yang ditolak.',
            confirmButtonText: 'Ya, tolak',
        });
        if (!confirmed) return;

        router.post(route('log-kegiatan.reject', log.route_key), {}, {
            preserveScroll: true,
            onStart: () => setIsRejecting(true),
            onFinish: () => setIsRejecting(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Detail Log Kegiatan
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Informasi lengkap kegiatan KKN beserta dokumentasi dan luaran.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={route('log-kegiatan.index')}>
                            <SecondaryButton>Kembali</SecondaryButton>
                        </Link>
                        {!isAdmin && ['draft', 'ditolak'].includes(log.status) && (
                            <Link href={route('log-kegiatan.edit', { log_kegiatan: log.route_key, step: log.current_step })}>
                                <PrimaryButton>{log.status === 'ditolak' ? 'Perbaiki Kegiatan' : 'Ubah Kegiatan'}</PrimaryButton>
                            </Link>
                        )}
                        {isAdmin && log.status === 'menunggu' && (
                            <>
                                <SecondaryButton onClick={handleReject} disabled={isRejecting || isApproving}>
                                    {isRejecting ? 'Memproses...' : 'Tolak'}
                                </SecondaryButton>
                                <PrimaryButton onClick={handleApprove} disabled={isApproving || isRejecting}>
                                    {isApproving ? 'Memproses...' : 'Setujui'}
                                </PrimaryButton>
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Detail Log Kegiatan`} />

            <div className="portal-log-detail-page py-8">
                <div className="portal-log-detail-content mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                    {/* Header Card: Status & Peserta */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b pb-4">
                            <div>
                                <span className="text-xs uppercase tracking-wider text-gray-400 font-semibold">
                                    Jenis Kegiatan
                                </span>
                                <h3 className="text-lg font-bold text-indigo-700">
                                    {log.master_kegiatan?.nama_kegiatan}
                                </h3>
                                <p className="text-xs font-mono text-gray-500">
                                    Kode: {log.master_kegiatan?.kode_kegiatan} | Kategori: {log.master_kegiatan?.kategori}
                                </p>
                            </div>
                            <div className="mt-3 sm:mt-0">
                                {log.status === 'disetujui' ? (
                                    <span className="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                        Status: Disetujui
                                    </span>
                                ) : log.status === 'menunggu' ? (
                                    <span className="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                                        Status: Menunggu
                                    </span>
                                ) : log.status === 'ditolak' ? (
                                    <span className="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                        Status: Ditolak
                                    </span>
                                ) : (
                                    <span className="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">
                                        ✎ Status: Draf (Tahap {log.current_step})
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Mahasiswa Info */}
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-sm">
                            <div>
                                <span className="text-xs text-gray-500 block">Nama Mahasiswa</span>
                                <span className="font-semibold text-gray-800">{log.peserta?.nama_lengkap ?? '-'}</span>
                            </div>
                            <div>
                                <span className="text-xs text-gray-500 block">NIM</span>
                                <span className="font-semibold text-gray-800">{log.peserta?.nim ?? '-'}</span>
                            </div>
                            <div>
                                <span className="text-xs text-gray-500 block">Program Studi</span>
                                <span className="font-semibold text-gray-800">{log.peserta?.prodi?.nama_prodi ?? '-'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Step 1 Details: Kehadiran & Lokasi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <h4 className="text-base font-semibold text-gray-800 border-b pb-2">
                            1. Informasi Kehadiran & Lokasi
                        </h4>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-sm">
                            <div>
                                <span className="text-xs text-gray-500 block">Tanggal Kehadiran</span>
                                <span className="font-medium text-gray-800">{log.tanggal_kehadiran}</span>
                            </div>
                            <div>
                                <span className="text-xs text-gray-500 block">Jam Kehadiran</span>
                                <span className="font-medium text-gray-800">{log.jam_kehadiran}</span>
                            </div>
                            <div>
                                <span className="text-xs text-gray-500 block">Lokasi</span>
                                <span className="font-medium text-gray-800">📍 {log.lokasi_kegiatan}</span>
                            </div>
                        </div>

                        {hasCoords && (
                            <div className="mt-4 rounded-md bg-gray-50 p-3 text-xs flex items-center justify-between">
                                <div>
                                    <span className="text-gray-500">Koordinat GPS: </span>
                                    <span className="font-mono font-medium">{log.latitude}, {log.longitude}</span>
                                </div>
                                <a
                                    href={mapUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-indigo-600 hover:text-indigo-800 font-semibold underline"
                                >
                                    Buka di Google Maps ↗
                                </a>
                            </div>
                        )}
                    </div>

                    {/* Step 2 Details: Deskripsi & Foto Dokumentasi */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <h4 className="text-base font-semibold text-gray-800 border-b pb-2">
                            2. Deskripsi & Dokumentasi Foto
                        </h4>
                        <div className="pt-4 text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                            {log.deskripsi_kegiatan || <span className="text-gray-400 italic">Belum ada deskripsi kegiatan.</span>}
                        </div>

                        {photoFiles.length > 0 ? (
                            <div className="mt-4">
                                <span className="text-xs text-gray-500 block mb-2">Dokumentasi Foto Kegiatan:</span>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    {photoFiles.map((file) => (
                                        <a key={file.id} href={file.url} target="_blank" rel="noreferrer">
                                            <img
                                                src={file.url}
                                                alt={file.nama_file_asli}
                                                className="h-64 w-full rounded-lg border object-cover shadow-sm hover:opacity-90 transition"
                                            />
                                            <span className="mt-1 block truncate text-xs text-gray-500">{file.nama_file_asli}</span>
                                        </a>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <p className="mt-4 text-xs italic text-gray-400">Belum ada foto dokumentasi yang diunggah.</p>
                        )}
                    </div>

                    {/* Step 3 Details: Luaran & File Berkas */}
                    <div className="rounded-lg bg-white p-6 shadow-sm">
                        <h4 className="text-base font-semibold text-gray-800 border-b pb-2">
                            3. Luaran Kegiatan & Dokumen Berkas
                        </h4>
                        <div className="pt-4 text-sm text-gray-700 whitespace-pre-line leading-relaxed">
                            {log.deskripsi_luaran || <span className="text-gray-400 italic">Belum ada deskripsi luaran.</span>}
                        </div>

                        {outputFiles.length > 0 ? (
                            <div className="mt-4 flex flex-col gap-2">
                                {outputFiles.map((file) => (
                                    <a
                                        key={file.id}
                                        href={file.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="inline-flex max-w-full items-center gap-2 truncate rounded-md border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition"
                                    >
                                        {file.nama_file_asli}
                                    </a>
                                ))}
                            </div>
                        ) : (
                            <p className="mt-4 text-xs italic text-gray-400">Belum ada berkas luaran yang diunggah.</p>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
