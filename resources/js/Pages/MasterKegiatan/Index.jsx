import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import SortableHeading from '@/Components/SortableHeading';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function MasterKegiatanIndex({ kegiatans, filters, canManage, prodis = [] }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [kodeProdi, setKodeProdi] = useState(filters?.kode_prodi || '');
    const filterTimer = useRef(null);
    const [modalState, setModalState] = useState({ isOpen: false, mode: 'create', item: null });
    const [deleteItem, setDeleteItem] = useState(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        kode_kegiatan: '',
        nama_kegiatan: '',
        kategori: '',
        kode_prodi: '',
    });

    useEffect(() => () => clearTimeout(filterTimer.current), []);

    const applyFilters = (nextSearch, nextKodeProdi, delay = 300) => {
        clearTimeout(filterTimer.current);
        filterTimer.current = setTimeout(() => {
            router.get(route('master-kegiatan.index'), {
                search: nextSearch || undefined,
                kode_prodi: nextKodeProdi || undefined,
                sort: filters?.sort,
                direction: filters?.direction,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, delay);
    };

    const handleSort = (column) => {
        clearTimeout(filterTimer.current);
        const direction = filters?.sort === column && filters?.direction === 'asc' ? 'desc' : 'asc';

        router.get(route('master-kegiatan.index'), {
            search: search || undefined,
            kode_prodi: kodeProdi || undefined,
            sort: column,
            direction,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const openCreateModal = () => {
        clearErrors();
        reset();
        setModalState({ isOpen: true, mode: 'create', item: null });
    };

    const openEditModal = (item) => {
        clearErrors();
        setData({
            kode_kegiatan: item.kode_kegiatan,
            nama_kegiatan: item.nama_kegiatan,
            kategori: item.kategori,
            kode_prodi: item.kode_prodi,
        });
        setModalState({ isOpen: true, mode: 'edit', item });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: 'create', item: null });
        reset();
        clearErrors();
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (modalState.mode === 'create') {
            post(route('master-kegiatan.store'), {
                onSuccess: () => closeModal(),
            });
        } else {
            put(route('master-kegiatan.update', modalState.item.id), {
                onSuccess: () => closeModal(),
            });
        }
    };

    const handleDelete = async (item) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus master kegiatan?',
            text: `Data ${item.nama_kegiatan} (${item.kode_kegiatan}) akan dihapus.`,
            confirmButtonText: 'Ya, hapus',
        });
        if (!confirmed) return;

        router.delete(route('master-kegiatan.destroy', item.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Master Kegiatan
                    </h2>
                    {canManage && (
                        <PrimaryButton onClick={openCreateModal}>
                            + Tambah Kegiatan
                        </PrimaryButton>
                    )}
                </div>
            }
        >
            <Head title="Master Kegiatan" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Search and Filters */}
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex w-full flex-col gap-3 sm:max-w-2xl sm:flex-row sm:items-center">
                            <TextInput
                                type="text"
                                className="w-full"
                                placeholder="Cari kode, kegiatan, kategori, atau prodi..."
                                value={search}
                                onChange={(e) => {
                                    const value = e.target.value;
                                    setSearch(value);
                                    applyFilters(value, kodeProdi);
                                }}
                            />
                            <select
                                aria-label="Filter berdasarkan program studi"
                                className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs"
                                value={kodeProdi}
                                onChange={(e) => {
                                    const value = e.target.value;
                                    setKodeProdi(value);
                                    applyFilters(search, value, 0);
                                }}
                            >
                                <option value="">Semua Program Studi</option>
                                {prodis.map((prodi) => (
                                    <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                        {prodi.nama_prodi}
                                    </option>
                                ))}
                            </select>
                            {(search || kodeProdi) && (
                                <SecondaryButton
                                    type="button"
                                    onClick={() => {
                                        clearTimeout(filterTimer.current);
                                        setSearch('');
                                        setKodeProdi('');
                                        router.get(route('master-kegiatan.index'), {
                                            sort: filters?.sort,
                                            direction: filters?.direction,
                                        }, { preserveState: true, preserveScroll: true, replace: true });
                                    }}
                                >
                                    Reset
                                </SecondaryButton>
                            )}
                        </div>
                    </div>

                    {/* Table */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left text-sm text-gray-700">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th scope="col" className="px-6 py-3">No</th>
                                        <SortableHeading label="Kode" column="kode_kegiatan" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Nama Kegiatan" column="nama_kegiatan" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Kategori" column="kategori" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Prodi" column="prodi" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Jumlah Log" column="log_kegiatans_count" sort={filters?.sort} direction={filters?.direction} onSort={handleSort} className="px-6 py-3" />
                                        {canManage && (
                                            <th scope="col" className="px-6 py-3 text-right">Aksi</th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {kegiatans.data && kegiatans.data.length > 0 ? (
                                        kegiatans.data.map((item, index) => (
                                            <tr key={item.id} className="hover:bg-gray-50">
                                                <td className="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                                    {(kegiatans.current_page - 1) * kegiatans.per_page + index + 1}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4 font-mono font-semibold text-indigo-600">
                                                    {item.kode_kegiatan}
                                                </td>
                                                <td className="px-6 py-4 font-medium text-gray-800">
                                                    {item.nama_kegiatan}
                                                </td>
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    <span className="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                                        {item.kategori}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">{item.prodi?.nama_prodi ?? item.kode_prodi}</td>
                                                <td className="whitespace-nowrap px-6 py-4 text-gray-500">
                                                    {item.log_kegiatans_count ?? 0} log
                                                </td>
                                                {canManage && (
                                                    <td className="whitespace-nowrap px-6 py-4 text-right space-x-2">
                                                        <SecondaryButton
                                                            onClick={() => openEditModal(item)}
                                                            className="text-xs"
                                                        >
                                                            Edit
                                                        </SecondaryButton>
                                                        <DangerButton
                                                            onClick={() => handleDelete(item)}
                                                            className="text-xs"
                                                        >
                                                            Hapus
                                                        </DangerButton>
                                                    </td>
                                                )}
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={canManage ? 7 : 6} className="py-8 text-center text-gray-500">
                                                Belum ada data master kegiatan.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination Links */}
                        {kegiatans.links && kegiatans.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                                <div className="text-xs text-gray-500">
                                    Menampilkan {kegiatans.from ?? 0} - {kegiatans.to ?? 0} dari {kegiatans.total} data
                                </div>
                                <div className="flex gap-1">
                                    {kegiatans.links.map((link, idx) => (
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

            {/* Modal Form Tambah / Edit */}
            <Modal show={modalState.isOpen} onClose={closeModal}>
                <form onSubmit={handleSubmit} className="p-6">
                    <h3 className="text-lg font-medium text-gray-900">
                        {modalState.mode === 'create' ? 'Tambah Master Kegiatan' : 'Edit Master Kegiatan'}
                    </h3>
                    <p className="mt-1 text-sm text-gray-600">
                        Isi data kegiatan yang akan digunakan sebagai referensi log kegiatan mahasiswa.
                    </p>

                    <div className="mt-6 space-y-4">
                        <div>
                            <InputLabel htmlFor="kode_kegiatan" value="Kode Kegiatan" />
                            <TextInput
                                id="kode_kegiatan"
                                type="text"
                                className="mt-1 block w-full"
                                placeholder="Contoh: KKN-001"
                                value={data.kode_kegiatan}
                                onChange={(e) => setData('kode_kegiatan', e.target.value)}
                                required
                            />
                            <InputError message={errors.kode_kegiatan} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="nama_kegiatan" value="Nama Kegiatan" />
                            <TextInput
                                id="nama_kegiatan"
                                type="text"
                                className="mt-1 block w-full"
                                placeholder="Contoh: Sosialisasi Pengolahan Sampah Desa"
                                value={data.nama_kegiatan}
                                onChange={(e) => setData('nama_kegiatan', e.target.value)}
                                required
                            />
                            <InputError message={errors.nama_kegiatan} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="kategori" value="Kategori" />
                            <TextInput
                                id="kategori"
                                type="text"
                                className="mt-1 block w-full"
                                placeholder="Contoh: Lingkungan, Sosialisasi, Pelatihan"
                                value={data.kategori}
                                onChange={(e) => setData('kategori', e.target.value)}
                                required
                            />
                            <InputError message={errors.kategori} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="kode_prodi" value="Program Studi" />
                            <select
                                id="kode_prodi"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={data.kode_prodi}
                                onChange={(e) => setData('kode_prodi', e.target.value)}
                                required
                            >
                                <option value="">Pilih program studi</option>
                                {prodis.map((prodi) => (
                                    <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                        {prodi.kode_prodi} — {prodi.nama_prodi}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.kode_prodi} className="mt-1" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal} type="button">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton disabled={processing || prodis.length === 0} type="submit">
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Konfirmasi Hapus */}
            <Modal show={Boolean(deleteItem)} onClose={() => setDeleteItem(null)}>
                <div className="p-6">
                    <h3 className="text-lg font-medium text-gray-900">
                        Konfirmasi Hapus
                    </h3>
                    <p className="mt-2 text-sm text-gray-600">
                        Apakah Anda yakin ingin menghapus master kegiatan <strong>{deleteItem?.nama_kegiatan}</strong> ({deleteItem?.kode_kegiatan})?
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteItem(null)}>
                            Batal
                        </SecondaryButton>
                        <DangerButton onClick={handleDelete}>
                            Ya, Hapus
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

