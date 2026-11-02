import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function ProdiIndex({ prodis = [] }) {
    const [modalState, setModalState] = useState({ isOpen: false, item: null });
    const [deleteItem, setDeleteItem] = useState(null);
    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        kode_prodi: '',
        nama_prodi: '',
    });

    const openCreateModal = () => {
        clearErrors();
        reset();
        setModalState({ isOpen: true, item: null });
    };

    const openEditModal = (prodi) => {
        clearErrors();
        setData({ kode_prodi: prodi.kode_prodi, nama_prodi: prodi.nama_prodi });
        setModalState({ isOpen: true, item: prodi });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, item: null });
        reset();
        clearErrors();
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        if (modalState.item) {
            put(route('prodi.update', modalState.item.kode_prodi), { onSuccess: closeModal });
        } else {
            post(route('prodi.store'), { onSuccess: closeModal });
        }
    };

    const handleDelete = () => {
        if (!deleteItem) {
            return;
        }

        router.delete(route('prodi.destroy', deleteItem.kode_prodi), {
            onSuccess: () => setDeleteItem(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={(
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Program Studi</h2>
                    <PrimaryButton onClick={openCreateModal}>+ Tambah Prodi</PrimaryButton>
                </div>
            )}
        >
            <Head title="Program Studi" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left text-sm text-gray-700">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th className="px-6 py-3">Kode Prodi</th>
                                        <th className="px-6 py-3">Nama Prodi</th>
                                        <th className="px-6 py-3">Peserta</th>
                                        <th className="px-6 py-3">Master Kegiatan</th>
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
                                            <td className="px-6 py-4">{prodi.pesertas_count}</td>
                                            <td className="px-6 py-4">{prodi.master_kegiatans_count}</td>
                                            <td className="space-x-2 whitespace-nowrap px-6 py-4 text-right">
                                                <SecondaryButton onClick={() => openEditModal(prodi)} className="text-xs">
                                                    Edit
                                                </SecondaryButton>
                                                <DangerButton onClick={() => setDeleteItem(prodi)} className="text-xs">
                                                    Hapus
                                                </DangerButton>
                                            </td>
                                        </tr>
                                    )) : (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-10 text-center text-gray-500">
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
                    <h3 className="text-lg font-medium text-gray-900">
                        {modalState.item ? 'Ubah Program Studi' : 'Tambah Program Studi'}
                    </h3>
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