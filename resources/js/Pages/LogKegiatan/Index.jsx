import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import SortableHeading from '@/Components/SortableHeading';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';

export default function LogKegiatanIndex({ logs, masterKegiatans, filters, isAdmin, sessions = [], prodis = [], pendingCount = 0 }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [status, setStatus] = useState(filters?.status || '');
    const [masterId, setMasterId] = useState(filters?.master_kegiatan_id || '');
    const [date, setDate] = useState(filters?.tanggal || '');
    const [session, setSession] = useState(filters?.sesi || '');
    const [kodeProdi, setKodeProdi] = useState(filters?.kode_prodi || '');
    const [processingId, setProcessingId] = useState(null);
    const [isApprovingAll, setIsApprovingAll] = useState(false);
    const [showFilters, setShowFilters] = useState(false);
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
                kode_prodi: kodeProdi || undefined,
                tanggal: date || undefined,
                sesi: session || undefined,
                sort: filters?.sort,
                direction: filters?.direction,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status, masterId, kodeProdi, date, session]);

    const handleSort = (column) => {
        const direction = filters?.sort === column && filters?.direction === 'asc' ? 'desc' : 'asc';

        router.get(route('log-kegiatan.index'), {
            search: search || undefined,
            status: status || undefined,
            master_kegiatan_id: masterId || undefined,
            kode_prodi: kodeProdi || undefined,
            tanggal: date || undefined,
            sesi: session || undefined,
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

        router.post(route(`log-kegiatan.${action}`, item.route_key), {}, {
            preserveScroll: true,
            onStart: () => setProcessingId(item.id),
            onFinish: () => setProcessingId(null),
        });
    };

    const handleDeleteDraft = async (item) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus draft kegiatan?',
            text: 'Draft beserta berkas yang sudah diunggah akan dihapus permanen.',
            confirmButtonText: 'Ya, hapus draft',
        });

        if (!confirmed) return;

        router.delete(route('log-kegiatan.destroy', item.route_key), {
            preserveScroll: true,
        });
    };

    const handleApproveAll = async () => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Setujui semua laporan?',
            text: `${pendingCount} laporan menunggu yang cocok dengan filter aktif akan disetujui.`,
            confirmButtonText: 'Ya, setujui semua',
            icon: 'question',
        });

        if (!confirmed) return;

        router.post(route('log-kegiatan.approve-all'), {
            search: search || undefined,
            status: status || undefined,
            master_kegiatan_id: masterId || undefined,
            kode_prodi: kodeProdi || undefined,
            tanggal: date || undefined,
            sesi: session || undefined,
        }, {
            preserveScroll: true,
            onStart: () => setIsApprovingAll(true),
            onFinish: () => setIsApprovingAll(false),
        });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setMasterId('');
        setKodeProdi('');
        setDate('');
        setSession('');
    };

    const activeFilterCount = [search, status, masterId, kodeProdi, date, session].filter(Boolean).length;
    const isFiltered = activeFilterCount > 0;
    const exportFilters = {
        search: search || undefined,
        status: status || undefined,
        master_kegiatan_id: masterId || undefined,
        kode_prodi: kodeProdi || undefined,
        tanggal: date || undefined,
        sesi: session || undefined,
    };

    return (
        <AuthenticatedLayout>
            <Head title="Kegiatan" />

            <div className={`portal-logs-page py-8${isAdmin ? ' portal-logs-admin' : ' portal-logs-student'}`}>
                <div className="portal-logs-content mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <header className={`portal-dashboard-page-heading ${isAdmin ? 'portal-logs-admin-heading' : 'portal-logs-student-heading'}`}>
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal {isAdmin ? 'Admin' : 'Mahasiswa'} <span>/</span> Kegiatan
                            </p>
                            <h1>Kegiatan</h1>
                            <p>{isAdmin
                                ? 'Memantau kegiatan mahasiswa KKN yang telah diajukan.'
                                : 'Lihat dan kelola catatan kegiatan KKN Anda.'}
                            </p>
                        </div>
                    </header>

                    {/* Filter Bar */}
                    <div className="portal-filter-panel mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white p-3 shadow-sm">
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setShowFilters((isOpen) => !isOpen)}
                                aria-haspopup="dialog"
                                aria-expanded={showFilters}
                                className={`inline-flex h-10 items-center gap-2 rounded-md px-4 text-sm font-semibold ${showFilters ? 'bg-indigo-700 text-white' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}`}
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-4 w-4" aria-hidden="true">
                                    <path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5L4 5Z" />
                                </svg>
                                Filters
                                <span className={`inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs ${showFilters ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700'}`}>
                                    {activeFilterCount}
                                </span>
                            </button>

                            {showFilters && (
                                <section
                                    role="dialog"
                                    aria-label="Filter kegiatan"
                                    className="absolute left-0 top-full z-30 mt-2 max-h-[75vh] w-80 max-w-[calc(100vw-2rem)] overflow-y-auto rounded-lg border border-gray-200 bg-white p-5 shadow-xl"
                                >
                                    <header className="mb-5 flex items-center justify-between">
                                        <h2 className="text-base font-semibold text-gray-900">Filters</h2>
                                        <button
                                            type="button"
                                            onClick={() => setShowFilters(false)}
                                            aria-label="Tutup filter"
                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4" aria-hidden="true">
                                                <path d="m6 6 12 12M18 6 6 18" />
                                            </svg>
                                        </button>
                                    </header>

                                    <div className="space-y-4">

                                        <div>
                                            <label htmlFor="filter-status" className="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                                            <select
                                                id="filter-status"
                                                className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={status}
                                                onChange={(event) => setStatus(event.target.value)}
                                            >
                                                <option value="">Semua Status</option>
                                                {!isAdmin && <option value="draft">Draf</option>}
                                                <option value="menunggu">Menunggu</option>
                                                <option value="disetujui">Disetujui</option>
                                                <option value="ditolak">Ditolak</option>
                                            </select>
                                        </div>

                                        {isAdmin && (
                                            <>
                                                <div>
                                                    <label htmlFor="filter-date" className="mb-1.5 block text-sm font-medium text-gray-700">Tanggal</label>
                                                    <input
                                                        id="filter-date"
                                                        type="date"
                                                        value={date}
                                                        onChange={(event) => setDate(event.target.value)}
                                                        className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label htmlFor="filter-session" className="mb-1.5 block text-sm font-medium text-gray-700">Waktu</label>
                                                    <select
                                                        id="filter-session"
                                                        className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        value={session}
                                                        onChange={(event) => setSession(event.target.value)}
                                                    >
                                                        <option value="">Semua Waktu</option>
                                                        {sessions.map((item) => (
                                                            <option key={item} value={item}>
                                                                {item.endsWith('(Pagi)') ? 'Pagi' : item.endsWith('(Siang)') ? 'Siang' : 'Sore'}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </>
                                        )}

                                        <div>
                                            <label htmlFor="filter-activity" className="mb-1.5 block text-sm font-medium text-gray-700">Kegiatan</label>
                                            <select
                                                id="filter-activity"
                                                className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                value={masterId}
                                                onChange={(event) => setMasterId(event.target.value)}
                                            >
                                                <option value="">Semua Kegiatan</option>
                                                {masterKegiatans?.map((kegiatan) => (
                                                    <option key={kegiatan.id} value={kegiatan.id}>
                                                        {kegiatan.kode_kegiatan} - {kegiatan.nama_kegiatan}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {isAdmin && (
                                            <div>
                                                <label htmlFor="filter-prodi" className="mb-1.5 block text-sm font-medium text-gray-700">Program Studi</label>
                                                <select
                                                    id="filter-prodi"
                                                    className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value={kodeProdi}
                                                    onChange={(event) => setKodeProdi(event.target.value)}
                                                >
                                                    <option value="">Semua Program Studi</option>
                                                    {prodis.map((prodi) => (
                                                        <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                                            {prodi.nama_prodi}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        )}
                                    </div>

                                    <footer className="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                                        <button
                                            type="button"
                                            onClick={handleReset}
                                            disabled={!isFiltered}
                                            className="text-sm font-medium text-gray-600 hover:text-gray-900 disabled:cursor-default disabled:text-gray-400"
                                        >
                                            Bersihkan filter
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => setShowFilters(false)}
                                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Selesai
                                        </button>
                                    </footer>
                                </section>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            {isAdmin && pendingCount > 0 && (
                                <button
                                    type="button"
                                    onClick={handleApproveAll}
                                    disabled={isApprovingAll}
                                    className="inline-flex min-h-10 items-center gap-2 rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4" aria-hidden="true">
                                        <path d="m3 10 4 4 10-10" />
                                    </svg>
                                    {isApprovingAll ? 'Memproses...' : `Setujui semua (${pendingCount})`}
                                </button>
                            )}
                            {isAdmin && (
                                <a
                                    href={route('log-kegiatan.export', exportFilters)}
                                    title="Unduh laporan disetujui dalam format Excel"
                                    className="inline-flex min-h-10 items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className="h-4 w-4" aria-hidden="true">
                                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 17v4h14v-4" />
                                    </svg>
                                    Export Excel
                                </a>
                            )}
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
                                        <SortableHeading label="Tahap pengisian" column="current_step" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
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
                                                            Draf
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
                                                        Tahap {item.current_step} dari 7
                                                    </span>
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    <div className="inline-flex items-center gap-2">
                                                        <Link
                                                            href={route('log-kegiatan.show', item.route_key)}
                                                            title="Lihat detail"
                                                            aria-label={`Lihat detail log ${item.id}`}
                                                            className={isAdmin
                                                                ? 'inline-flex h-8 w-8 items-center justify-center rounded-full border border-indigo-200 font-bold text-indigo-700 hover:bg-indigo-50'
                                                                : 'inline-flex items-center rounded bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100'}
                                                        >
                                                            {isAdmin ? 'i' : 'Detail'}
                                                        </Link>
                                                        {!isAdmin && item.status === 'draft' && (
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteDraft(item)}
                                                                className="inline-flex items-center rounded bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                            >
                                                                Hapus draft
                                                            </button>
                                                        )}
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
