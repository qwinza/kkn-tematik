import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ dpps, prodis, filters, totalCount }) {
    const [search, setSearch] = useState(filters.search || '');
    const [kodeProdi, setKodeProdi] = useState(filters.kode_prodi || '');
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: 'create',
        item: null,
    });
    const [resetTarget, setResetTarget] = useState(null);
    const filterTimer = useRef(null);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        name: '',
        email: '',
        kode_prodi: '',
        password: '',
        password_confirmation: '',
    });

    const passwordForm = useForm({
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        return () => clearTimeout(filterTimer.current);
    }, []);

    const applyFilters = (nextSearch, nextKodeProdi, delay = 300) => {
        clearTimeout(filterTimer.current);

        filterTimer.current = setTimeout(() => {
            router.get(route('admin.dpp.index'), {
                search: nextSearch || undefined,
                kode_prodi: nextKodeProdi || undefined,
            }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, delay);
    };

    const handleResetFilters = () => {
        clearTimeout(filterTimer.current);
        setSearch('');
        setKodeProdi('');

        router.get(route('admin.dpp.index'), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const openCreateModal = () => {
        reset();
        clearErrors();

        setModalState({
            isOpen: true,
            mode: 'create',
            item: null,
        });
    };

    const openEditModal = (item) => {
        clearErrors();

        setData({
            name: item.name || '',
            email: item.email || '',
            kode_prodi: item.kode_prodi || '',
            password: '',
            password_confirmation: '',
        });

        setModalState({
            isOpen: true,
            mode: 'edit',
            item,
        });
    };

    const closeModal = () => {
        setModalState({
            isOpen: false,
            mode: 'create',
            item: null,
        });

        reset();
        clearErrors();
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        const isCreate = modalState.mode === 'create';

        const confirmed = await confirmWithSweetAlert({
            title: isCreate ? 'Tambah akun DPP?' : 'Simpan perubahan akun?',
            text: isCreate
                ? 'Akun DPP baru akan dibuat dan dikaitkan dengan program studi.'
                : 'Identitas dan program studi akun DPP akan diperbarui.',
            icon: 'question',
            confirmButtonText: isCreate ? 'Ya, tambah' : 'Ya, simpan',
        });

        if (!confirmed) return;

        if (isCreate) {
            post(route('admin.dpp.store'), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else {
            put(route('admin.dpp.update', modalState.item.id), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        }
    };

    const openResetModal = (item) => {
        passwordForm.reset();
        passwordForm.clearErrors();
        setResetTarget(item);
    };

    const closeResetModal = () => {
        setResetTarget(null);
        passwordForm.reset();
        passwordForm.clearErrors();
    };

    const handleResetPassword = async (event) => {
        event.preventDefault();

        const confirmed = await confirmWithSweetAlert({
            title: 'Reset password DPP?',
            text: `Password akun ${resetTarget.name} akan diganti. Pengguna wajib mengganti password saat login berikutnya.`,
            icon: 'warning',
            confirmButtonText: 'Ya, reset password',
        });

        if (!confirmed) return;

        passwordForm.put(
            route('admin.dpp.reset-password', resetTarget.id),
            {
                preserveScroll: true,
                onSuccess: closeResetModal,
            }
        );
    };

    const handleDelete = async (item) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus akun DPP?',
            text: `Akun ${item.name} (${item.email}) akan dihapus secara permanen.`,
            icon: 'warning',
            confirmButtonText: 'Ya, hapus akun',
        });

        if (!confirmed) return;

        router.delete(route('admin.dpp.destroy', item.id), {
            preserveScroll: true,
        });
    };

    const isFiltered = Boolean(search || kodeProdi);

    return (
        <AuthenticatedLayout>
            <Head title="Manajemen DPP" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="portal-dashboard-page-heading">
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal Admin <span>/</span> Manajemen DPP
                            </p>
                            <h1>Manajemen DPP</h1>
                            <p>
                                Kelola akun DPP dan penugasan program studi.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <span className="rounded-lg bg-white px-4 py-2 text-sm text-gray-600 shadow-sm">
                                Total DPP: <strong>{totalCount}</strong>
                            </span>

                            <PrimaryButton onClick={openCreateModal}>
                                + Tambah DPP
                            </PrimaryButton>
                        </div>
                    </header>

                    <section className="rounded-lg bg-white p-4 shadow-sm">
                        <div className="flex flex-col gap-3 md:flex-row">
                            <TextInput
                                type="search"
                                className="w-full"
                                placeholder="Cari nama, email, atau program studi..."
                                value={search}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setSearch(value);
                                    applyFilters(value, kodeProdi);
                                }}
                            />

                            <select
                                aria-label="Filter program studi"
                                className="w-full rounded-md border-gray-300 text-sm shadow-sm md:max-w-xs"
                                value={kodeProdi}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setKodeProdi(value);
                                    applyFilters(search, value, 0);
                                }}
                            >
                                <option value="">Semua Program Studi</option>
                                {prodis.map((prodi) => (
                                    <option
                                        key={prodi.kode_prodi}
                                        value={prodi.kode_prodi}
                                    >
                                        {prodi.nama_prodi}
                                    </option>
                                ))}
                            </select>

                            {isFiltered && (
                                <SecondaryButton
                                    type="button"
                                    onClick={handleResetFilters}
                                >
                                    Reset
                                </SecondaryButton>
                            )}
                        </div>
                    </section>

                    <section className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                    <tr>
                                        <th className="px-6 py-4">No.</th>
                                        <th className="px-6 py-4">Nama DPP</th>
                                        <th className="px-6 py-4">Email</th>
                                        <th className="px-6 py-4">Program Studi</th>
                                        <th className="px-6 py-4">Status Password</th>
                                        <th className="px-6 py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-gray-200">
                                    {dpps.data.length > 0 ? (
                                        dpps.data.map((item, index) => (
                                            <tr
                                                key={item.id}
                                                className="hover:bg-gray-50"
                                            >
                                                <td className="whitespace-nowrap px-6 py-4">
                                                    {(dpps.current_page - 1) *
                                                        dpps.per_page +
                                                        index +
                                                        1}
                                                </td>

                                                <td className="px-6 py-4 font-medium text-gray-900">
                                                    {item.name}
                                                </td>

                                                <td className="px-6 py-4 text-gray-600">
                                                    {item.email}
                                                </td>

                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-800">
                                                        {item.prodi || '-'}
                                                    </div>
                                                    <div className="text-xs text-gray-500">
                                                        {item.fakultas || item.kode_prodi || ''}
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4">
                                                    {item.must_change_password ? (
                                                        <span className="rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">
                                                            Wajib ganti
                                                        </span>
                                                    ) : (
                                                        <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                                            Aktif
                                                        </span>
                                                    )}
                                                </td>

                                                <td className="whitespace-nowrap px-6 py-4 text-right">
                                                    <div className="flex flex-wrap justify-end gap-2">
                                                        <SecondaryButton
                                                            type="button"
                                                            onClick={() => openEditModal(item)}
                                                        >
                                                            Edit
                                                        </SecondaryButton>

                                                        <SecondaryButton
                                                            type="button"
                                                            onClick={() => openResetModal(item)}
                                                        >
                                                            Reset Password
                                                        </SecondaryButton>

                                                        <DangerButton
                                                            type="button"
                                                            onClick={() => handleDelete(item)}
                                                        >
                                                            Hapus
                                                        </DangerButton>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-6 py-10 text-center text-gray-500"
                                            >
                                                Belum ada akun DPP yang ditemukan.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {dpps.links && dpps.links.length > 3 && (
                            <div className="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs text-gray-500">
                                    Menampilkan {dpps.from || 0} sampai {dpps.to || 0} dari {dpps.total} akun
                                </p>

                                <div className="flex flex-wrap gap-1">
                                    {dpps.links.map((link, index) => (
                                        link.url ? (
                                            <Link
                                                key={index}
                                                href={link.url}
                                                preserveState
                                                className={`rounded px-3 py-2 text-xs ${
                                                    link.active
                                                        ? 'bg-indigo-600 text-white'
                                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                                }`}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ) : (
                                            <span
                                                key={index}
                                                className="rounded px-3 py-2 text-xs text-gray-400"
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        )
                                    ))}
                                </div>
                            </div>
                        )}
                    </section>
                </div>
            </div>

            <Modal
                show={modalState.isOpen}
                onClose={closeModal}
                maxWidth="xl"
            >
                <form onSubmit={handleSubmit} className="space-y-4 p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {modalState.mode === 'create'
                            ? 'Tambah Akun DPP'
                            : 'Edit Akun DPP'}
                    </h2>

                    <div>
                        <InputLabel htmlFor="dpp-name" value="Nama Lengkap" />
                        <TextInput
                            id="dpp-name"
                            className="mt-1 block w-full"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            required
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="dpp-email" value="Email" />
                        <TextInput
                            id="dpp-email"
                            type="email"
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            required
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel htmlFor="dpp-prodi" value="Program Studi" />
                        <select
                            id="dpp-prodi"
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm"
                            value={data.kode_prodi}
                            onChange={(event) => setData('kode_prodi', event.target.value)}
                            required
                        >
                            <option value="">Pilih Program Studi</option>
                            {prodis.map((prodi) => (
                                <option
                                    key={prodi.kode_prodi}
                                    value={prodi.kode_prodi}
                                >
                                    {prodi.kode_prodi} - {prodi.nama_prodi}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.kode_prodi} className="mt-1" />
                    </div>

                    {modalState.mode === 'create' && (
                        <>
                            <div>
                                <InputLabel htmlFor="dpp-password" value="Password Awal" />
                                <TextInput
                                    id="dpp-password"
                                    type="password"
                                    className="mt-1 block w-full"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="dpp-password-confirmation" value="Konfirmasi Password" />
                                <TextInput
                                    id="dpp-password-confirmation"
                                    type="password"
                                    className="mt-1 block w-full"
                                    value={data.password_confirmation}
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                    required
                                    minLength={8}
                                    autoComplete="new-password"
                                />
                            </div>
                        </>
                    )}

                    <p className="text-xs text-gray-500">
                        Akun baru akan diwajibkan mengganti password saat login pertama kali.
                    </p>

                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={processing || prodis.length === 0}
                        >
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal
                show={Boolean(resetTarget)}
                onClose={closeResetModal}
                maxWidth="xl"
            >
                <form onSubmit={handleResetPassword} className="space-y-4 p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        Reset Password DPP
                    </h2>

                    <p className="text-sm text-gray-600">
                        Akun: {resetTarget?.name}
                    </p>

                    <div>
                        <InputLabel htmlFor="reset-password" value="Password Baru" />
                        <TextInput
                            id="reset-password"
                            type="password"
                            className="mt-1 block w-full"
                            value={passwordForm.data.password}
                            onChange={(event) => passwordForm.setData('password', event.target.value)}
                            required
                            minLength={8}
                            autoComplete="new-password"
                        />
                        <InputError
                            message={passwordForm.errors.password}
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="reset-password-confirmation" value="Konfirmasi Password Baru" />
                        <TextInput
                            id="reset-password-confirmation"
                            type="password"
                            className="mt-1 block w-full"
                            value={passwordForm.data.password_confirmation}
                            onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)}
                            required
                            minLength={8}
                            autoComplete="new-password"
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" onClick={closeResetModal}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={passwordForm.processing}
                        >
                            {passwordForm.processing ? 'Memproses...' : 'Reset Password'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}