import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';
import { Head, router, useForm } from '@inertiajs/react';
import SortableHeading from '@/Components/SortableHeading';
import { useEffect, useRef, useState } from 'react';

export default function ProdiIndex({ prodis = [], fakultasOptions = [], filters }) {
    const [modalState, setModalState] = useState({ isOpen: false, item: null });
    const [deleteItem, setDeleteItem] = useState(null);
    const [fakultas, setFakultas] = useState(filters.fakultas || '');
    const isInitialFilter = useRef(true);
    const { data, setData, put, processing, errors, reset, clearErrors } = useForm({
        kode_prodi: '',
        nama_prodi: '',
        fakultas: '',
    });

    const openEditModal = (prodi) => {
        clearErrors();
        setData({ kode_prodi: prodi.kode_prodi, nama_prodi: prodi.nama_prodi, fakultas: prodi.fakultas });
        setModalState({ isOpen: true, item: prodi });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, item: null });
        reset();
        clearErrors();
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        put(route('prodi.update', modalState.item.kode_prodi), { onSuccess: closeModal });
    };

    useEffect(() => {
        if (isInitialFilter.current) {
            isInitialFilter.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('prodi.index'), {
                fakultas: fakultas || undefined,
                sort: filters.sort,
                direction: filters.direction,
            }, {
                preserveState: true,
                replace: true,
            });
        }, 250);

        return () => clearTimeout(timeout);
    }, [fakultas]);

    const handleSort = (column) => {
        const direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';

        router.get(route('prodi.index'), {
            fakultas: fakultas || undefined,
            sort: column,
            direction,
        }, { preserveState: true, replace: true });
    };

    const handleDelete = async (prodi) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus program studi?',
            text: `Data ${prodi.kode_prodi} — ${prodi.nama_prodi} akan dihapus.`,
            confirmButtonText: 'Ya, hapus',
        });
        if (!confirmed) return;

        router.delete(route('prodi.destroy', prodi.kode_prodi));
    };

    return (
        <AuthenticatedLayout
            header={(
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Program Studi</h2>
                </div>
            )}
        >
            <Head title="Program Studi" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="flex flex-col gap-2 border-b border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <label htmlFor="filter-fakultas" className="text-sm font-medium text-gray-700">
                                Filter Fakultas
                            </label>
                            <select
                                id="filter-fakultas"
                                value={fakultas}
                                onChange={(event) => setFakultas(event.target.value)}
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-80"
                            >
                                <option value="">Semua Fakultas</option>
                                {fakultasOptions.map((option, index) => (
                                    <option key={`${option}-${index}`} value={option || '__empty'}>
                                        {option || 'Fakultas belum diisi'}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left text-sm text-gray-700">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <SortableHeading label="Kode Prodi" column="kode_prodi" sort={filters.sort} direction={filters.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Nama Prodi" column="nama_prodi" sort={filters.sort} direction={filters.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Fakultas" column="fakultas" sort={filters.sort} direction={filters.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Peserta" column="pesertas_count" sort={filters.sort} direction={filters.direction} onSort={handleSort} className="px-6 py-3" />
                                        <SortableHeading label="Jadwal Kegiatan" column="master_kegiatans_count" sort={filters.sort} direction={filters.direction} onSort={handleSort} className="px-6 py-3" />
                                        <th className="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {prodis.length > 0 ? prodis.map((prodi) => (
                                        <tr key={prodi.kode_prodi} className="hover:bg-gray-50">
                                            <td className="whitespace-nowrap px-6 py-4 font-mono font-semibold text-indigo-600">
                                                {prodi.kode_prodi}
                                            </td>
                                            <td className="px-6 py-4 font-medium text-gray-800">{prodi.nama_prodi}</td>
                                            <td className="px-6 py-4">{prodi.fakultas || '-'}</td>
                                            <td className="px-6 py-4">{prodi.pesertas_count}</td>
                                            <td className="px-6 py-4">{prodi.master_kegiatans_count}</td>
                                            <td className="space-x-2 whitespace-nowrap px-6 py-4 text-right">
                                                <SecondaryButton onClick={() => openEditModal(prodi)} className="text-xs">
                                                    Edit
                                                </SecondaryButton>
                                                <DangerButton onClick={() => handleDelete(prodi)} className="text-xs">
                                                    Hapus
                                                </DangerButton>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-10 text-center text-gray-500">
                                                Belum ada data program studi.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={modalState.isOpen} onClose={closeModal}>
                <form onSubmit={handleSubmit} className="space-y-5 p-6">
                    <h3 className="text-lg font-medium text-gray-900">Ubah Program Studi</h3>
                    <div>
                        <InputLabel htmlFor="kode_prodi" value="Kode Prodi" />
                        <TextInput
                            id="kode_prodi"
                            className="mt-1 block w-full"
                            value={data.kode_prodi}
                            onChange={(event) => setData('kode_prodi', event.target.value)}
                            maxLength={20}
                            required
                        />
                        <InputError message={errors.kode_prodi} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="nama_prodi" value="Nama Prodi" />
                        <TextInput
                            id="nama_prodi"
                            className="mt-1 block w-full"
                            value={data.nama_prodi}
                            onChange={(event) => setData('nama_prodi', event.target.value)}
                            required
                        />
                        <InputError message={errors.nama_prodi} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="fakultas" value="Fakultas" />
                        <TextInput
                            id="fakultas"
                            className="mt-1 block w-full"
                            value={data.fakultas}
                            onChange={(event) => setData('fakultas', event.target.value)}
                            required
                        />
                        <InputError message={errors.fakultas} className="mt-1" />
                    </div>
                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeModal}>Batal</SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={Boolean(deleteItem)} onClose={() => setDeleteItem(null)}>
                <div className="space-y-5 p-6">
                    <h3 className="text-lg font-medium text-gray-900">Hapus Program Studi</h3>
                    <p className="text-sm text-gray-600">
                        Hapus {deleteItem?.kode_prodi} — {deleteItem?.nama_prodi}?
                    </p>
                    <div className="flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setDeleteItem(null)}>Batal</SecondaryButton>
                        <DangerButton type="button" onClick={handleDelete}>Hapus</DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
