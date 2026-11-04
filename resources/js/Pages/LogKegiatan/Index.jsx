import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import SortableHeading from '@/Components/SortableHeading';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';

export default function LogKegiatanIndex({ logs, masterKegiatans, filters, isAdmin }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [masterId, setMasterId] = useState(filters?.master_kegiatan_id || '');
    const [processingId, setProcessingId] = useState(null);
    const isInitialFilter = useRef(true);

    useEffect(() => {
        if (isInitialFilter.current) {
            isInitialFilter.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('log-kegiatan.index'), {
                search: search || undefined,
                status: status || undefined,
                master_kegiatan_id: masterId || undefined,
                sort: filters?.sort,
                direction: filters?.direction,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status, masterId]);

    const handleSort = (column) => {
        const direction = filters?.sort === column && filters?.direction === 'asc' ? 'desc' : 'asc';

        router.get(route('log-kegiatan.index'), {
            search: search || undefined,
            status: status || undefined,
            master_kegiatan_id: masterId || undefined,
            sort: column,
            direction,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const handleStatusAction = async (action, item) => {
        const isApprove = action === 'approve';
        const confirmed = await confirmWithSweetAlert({
            title: isApprove ? 'Setujui laporan ini?' : 'Tolak laporan ini?',
            text: isApprove
                ? 'Status laporan akan berubah menjadi disetujui.'
                : 'Mahasiswa perlu memperbaiki laporan yang ditolak.',
            confirmButtonText: isApprove ? 'Ya, setujui' : 'Ya, tolak',
            icon: isApprove ? 'question' : 'warning',
        });
        if (!confirmed) return;

        router.post(route(`log-kegiatan.${action}`, item.id), {}, {
            preserveScroll: true,
            onStart: () => setProcessingId(item.id),
            onFinish: () => setProcessingId(null),
        });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setMasterId('');
    };

    const isFiltered = Boolean(search || status || masterId);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Log Kegiatan KKN
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            {isAdmin
                                ? 'Memantau seluruh aktivitas log kegiatan mahasiswa KKN.'
                                : 'Catat dan kelola laporan kegiatan harian Anda dengan alur stepper berjenjang.'}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Log Kegiatan" />

            <div className="portal-logs-page py-8">
                <div className="portal-logs-content mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Filter Bar */}
                    <div className="portal-filter-panel mb-6 rounded-lg bg-white p-4 shadow-sm">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <div>
                                <TextInput
                                    type="text"
                                    className="w-full text-sm"
                                    placeholder={isAdmin ? "Cari deskripsi, nama/NIM peserta..." : "Cari deskripsi kegiatan/luaran..."}
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>

                            <div>
                                <select
                                    className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                >
                                    <option value="">Semua Status</option>
                                    {!isAdmin && <option value="draft">Draft (Belum Selesai)</option>}
                                    <option value="menunggu">Menunggu</option>
                                    <option value="disetujui">Disetujui</option>
                                    <option value="ditolak">Ditolak</option>
                                </select>
                            </div>

                            <div>
                                <select
                                    className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={masterId}
                                    onChange={(e) => setMasterId(e.target.value)}
                                >
                                    <option value="">Semua Master Kegiatan</option>
                                    {masterKegiatans?.map((kegiatan) => (
                                        <option key={kegiatan.id} value={kegiatan.id}>
                                            {kegiatan.kode_kegiatan} - {kegiatan.nama_kegiatan}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex gap-2">
                                {isFiltered && (
                                    <SecondaryButton type="button" onClick={handleReset}>
                                        Reset
                                    </SecondaryButton>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="portal-table-shell overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="portal-data-table min-w-full divide-y divide-gray-200 text-left text-sm text-gray-700">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th scope="col" className="px-6 py-3">No</th>
                                        {isAdmin && <SortableHeading label="Mahasiswa" column="mahasiswa" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />}
                                        <SortableHeading label="Kegiatan" column="kegiatan" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Waktu & Lokasi" column="tanggal_kehadiran" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Status" column="status" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Tahap Stepper" column="current_step" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <th scope="col" className="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {logs.data && logs.data.length > 0 ? (
                                        logs.data.map((item, index) => (
                                            <tr key={item.id} className="hover:bg-gray-50">
                                                <td className="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                                    {(logs.current_page - 1) * logs.per_page + index + 1}
                                                </td>
                                                {isAdmin && (
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="font-semibold text-gray-900">
                                                            {item.peserta?.nama_lengkap ?? '-'}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            NIM: {item.peserta?.nim ?? '-'}
                                                        </div>
                                                    </td>
                                                )}
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-indigo-700">
                                                        {item.master_kegiatan?.nama_kegiatan ?? 'Kegiatan belum dipilih'}
                                                    </div>
                                                    {item.master_kegiatan?.kode_kegiatan && (
                                                        <div className="text-xs text-gray-500 font-mono">
                                                            {item.master_kegiatan.kode_kegiatan}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <div className="text-gray-900">
                                                        {item.tanggal_kehadiran || 'Tanggal belum diisi'}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {item.jam_kehadiran || 'Waktu belum diisi'}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {item.lokasi_kegiatan ? `📍 ${item.lokasi_kegiatan}` : 'Lokasi belum diisi'}
                                                    </div>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    {item.status === 'disetujui' ? (
                                                        <span className="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                                            Disetujui
                                                        </span>
                                                    ) : item.status === 'menunggu' ? (
                                                        <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                                            Menunggu
                                                        </span>
                                                    ) : item.status === 'ditolak' ? (
                                                        <span className="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                                            Ditolak
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">
                                                            Draft
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                                        Step {item.current_step} dari 7
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    <div className="inline-flex items-center gap-2">
                                                        <Link
                                                            href={route('log-kegiatan.show', item.id)}
                                                            title="Lihat detail"
                                                            aria-label={`Lihat detail log ${item.id}`}
                                                            className={isAdmin
                                                                ? 'inline-flex h-8 w-8 items-center justify-center rounded-full border border-indigo-200 font-bold text-indigo-700 hover:bg-indigo-50'
                                                                : 'inline-flex items-center rounded bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100'}
                                                        >
                                                            {isAdmin ? 'i' : 'Detail'}
                                                        </Link>
                                                        {isAdmin && item.status === 'menunggu' && (
                                                            <>
                                                                <button
                                                                    type="button"
                                                                    title="Setujui log"
                                                                    aria-label={`Setujui log ${item.id}`}
                                                                    disabled={processingId === item.id}
                                                                    onClick={() => handleStatusAction('approve', item)}
                                                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-green-200 text-green-700 hover:bg-green-50 disabled:opacity-50"
                                                                >
                                                                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 fill-none stroke-current stroke-2">
                                                                        <path d="m4 10 4 4 8-8" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    title="Tolak log"
                                                                    aria-label={`Tolak log ${item.id}`}
                                                                    disabled={processingId === item.id}
                                                                    onClick={() => handleStatusAction('reject', item)}
                                                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 text-red-700 hover:bg-red-50 disabled:opacity-50"
                                                                >
                                                                    <svg aria-hidden="true" viewBox="0 0 20 20" className="h-4 w-4 fill-none stroke-current stroke-2">
                                                                        <path d="m5 5 10 10M15 5 5 15" />
                                                                    </svg>
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={isAdmin ? 7 : 6} className="py-8 text-center text-gray-500">
                                                Belum ada catatan log kegiatan.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Links */}
                        {logs.links && logs.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                                <div className="text-xs text-gray-500">
                                    Menampilkan {logs.from ?? 0} - {logs.to ?? 0} dari {logs.total} data
                                </div>
                                <div className="flex gap-1">
                                    {logs.links.map((link, idx) => (
                                        <button
                                            key={idx}
                                            onClick={() => link.url && router.get(link.url)}
                                            disabled={!link.url}
                                            className={`rounded px-3 py-1 text-xs font-medium transition ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white'
                                                    : link.url
                                                    ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                    : 'cursor-not-allowed text-gray-400'
                                            }`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

        </AuthenticatedLayout>
    );
}
