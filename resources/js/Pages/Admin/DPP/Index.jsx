
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function PendingRequestRow({ item, prodis, number }) {
    const approvalForm = useForm({
        kode_prodi: '',
    });

    const rejectionForm = useForm({
        rejection_reason: '',
    });

    const handleApprove = async () => {
        if (!approvalForm.data.kode_prodi) {
            return;
        }

        const confirmed = await confirmWithSweetAlert({
            title: 'Setujui akun dosen?',
            text: `Akun ${item.nama} akan dibuat sebagai DPP dan dikaitkan dengan program studi yang dipilih.`,
            icon: 'question',
            confirmButtonText: 'Ya, setujui',
        });

        if (!confirmed) return;

        approvalForm.post(
            route('admin.dpp.access-requests.approve', item.id),
            {
                preserveScroll: true,
                onSuccess: () => approvalForm.reset(),
            }
        );
    };

    const handleReject = async () => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Tolak permintaan dosen?',
            text: `Permintaan akses dari ${item.nama} akan ditolak.`,
            icon: 'warning',
            confirmButtonText: 'Ya, tolak',
        });

        if (!confirmed) return;

        rejectionForm.post(
            route('admin.dpp.access-requests.reject', item.id),
            {
                preserveScroll: true,
                onSuccess: () => rejectionForm.reset(),
            }
        );
    };

    return (
        <tr className="align-top hover:bg-gray-50">
            <td className="whitespace-nowrap px-5 py-4 text-gray-600">
                {number}
            </td>

            <td className="min-w-52 px-5 py-4">
                <p className="font-semibold text-gray-900">
                    {item.nama}
                </p>

                <p className="mt-1 text-xs text-gray-500">
                    Username: {item.username_sikad}
                </p>

                <p className="text-xs text-gray-500">
                    Nomor Dosen: {item.nomor_dosen}
                </p>

                <p className="text-xs text-gray-500">
                    NIDN: {item.nidn || '-'}
                </p>

                <p className="mt-1 text-xs text-gray-500">
                    {item.gelar_depan || ''}{' '}
                    {item.gelar_belakang || ''}
                </p>
            </td>

            <td className="whitespace-nowrap px-5 py-4 text-gray-600">
                <p>Fakultas: {item.kode_fakultas_sikad || '-'}</p>
                <p className="mt-1">
                    Prodi SIKAD: {item.kode_prodi_sikad || '-'}
                </p>

                <p className="mt-2 text-xs">
                    Status SIKAD: {item.status_sikad || '-'}
                </p>
            </td>

            <td className="whitespace-nowrap px-5 py-4 text-gray-600">
                {item.created_at || '-'}
            </td>

            <td className="min-w-56 px-5 py-4">
                <label
                    htmlFor={`prodi-${item.id}`}
                    className="mb-2 block text-xs font-semibold text-gray-700"
                >
                    Program Studi Lokal
                </label>

                <select
                    id={`prodi-${item.id}`}
                    className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                    value={approvalForm.data.kode_prodi}
                    onChange={(event) =>
                        approvalForm.setData(
                            'kode_prodi',
                            event.target.value
                        )
                    }
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

                <InputError
                    message={approvalForm.errors.kode_prodi}
                    className="mt-1"
                />

                <div className="mt-3 flex flex-col gap-2">
                    <button
                        type="button"
                        onClick={handleApprove}
                        disabled={
                            approvalForm.processing ||
                            rejectionForm.processing ||
                            !approvalForm.data.kode_prodi
                        }
                        className="rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {approvalForm.processing
                            ? 'Memproses...'
                            : 'Setujui Akun'}
                    </button>

                    <DangerButton
                        type="button"
                        onClick={handleReject}
                        disabled={
                            approvalForm.processing ||
                            rejectionForm.processing
                        }
                        className="justify-center"
                    >
                        {rejectionForm.processing
                            ? 'Memproses...'
                            : 'Tolak Permintaan'}
                    </DangerButton>
                </div>
            </td>
        </tr>
    );
}

function Pagination({ links }) {
    if (!links || links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap justify-end gap-1 border-t border-gray-200 px-5 py-4">
            {links.map((link, index) => (
                link.url ? (
                    <Link
                        key={index}
                        href={link.url}
                        preserveState
                        preserveScroll
                        className={`rounded px-3 py-2 text-xs ${
                            link.active
                                ? 'bg-red-700 text-white'
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
    );
}

export default function Index({
    dpps,
    pendingRequests,
    pendingCount,
    prodis,
    filters,
    totalCount,
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [kodeProdi, setKodeProdi] = useState(
        filters.kode_prodi || ''
    );

    const filterTimer = useRef(null);

    useEffect(() => {
        return () => clearTimeout(filterTimer.current);
    }, []);

    const applyFilters = (
        nextSearch,
        nextKodeProdi,
        delay = 300
    ) => {
        clearTimeout(filterTimer.current);

        filterTimer.current = setTimeout(() => {
            router.get(
                route('admin.dpp.index'),
                {
                    search: nextSearch || undefined,
                    kode_prodi: nextKodeProdi || undefined,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, delay);
    };

    const handleResetFilters = () => {
        clearTimeout(filterTimer.current);

        setSearch('');
        setKodeProdi('');

        router.get(
            route('admin.dpp.index'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleDelete = async (item) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus akun DPP?',
            text: `Akun ${item.name} akan dihapus secara permanen.`,
            icon: 'warning',
            confirmButtonText: 'Ya, hapus akun',
        });

        if (!confirmed) return;

        router.delete(
            route('admin.dpp.destroy', item.id),
            {
                preserveScroll: true,
            }
        );
    };

    const isFiltered = Boolean(search || kodeProdi);

    const requests = pendingRequests?.data || [];
    const activeDpps = dpps?.data || [];

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
                                Kelola permintaan akses dosen dan akun DPP.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <span className="rounded-lg bg-white px-4 py-2 text-sm text-gray-600 shadow-sm">
                                Total DPP: <strong>{totalCount}</strong>
                            </span>

                            <span className="rounded-lg bg-yellow-50 px-4 py-2 text-sm text-yellow-800 shadow-sm">
                                Menunggu: <strong>{pendingCount}</strong>
                            </span>
                        </div>
                    </header>

                    {/* PERMINTAAN AKSES DOSEN */}
                    <section className="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div className="flex flex-col gap-2 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-lg font-bold text-gray-900">
                                    Persetujuan Akun Dosen
                                </h2>

                                <p className="mt-1 text-sm text-gray-500">
                                    Daftar dosen yang berhasil diverifikasi oleh API SIKAD dan menunggu persetujuan Admin.
                                </p>
                            </div>

                            <span className="inline-flex w-fit items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800">
                                {pendingCount} Menunggu Persetujuan
                            </span>
                        </div>

                        {requests.length > 0 ? (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 text-left text-sm">
                                        <thead className="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                                            <tr>
                                                <th className="px-5 py-4">No.</th>
                                                <th className="px-5 py-4">Identitas Dosen</th>
                                                <th className="px-5 py-4">Data SIKAD</th>
                                                <th className="px-5 py-4">Tanggal Pengajuan</th>
                                                <th className="px-5 py-4">Persetujuan</th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-gray-200">
                                            {requests.map((item, index) => (
                                                <PendingRequestRow
                                                    key={item.id}
                                                    item={item}
                                                    prodis={prodis}
                                                    number={
                                                        (pendingRequests.current_page - 1) *
                                                        pendingRequests.per_page +
                                                        index +
                                                        1
                                                    }
                                                />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <Pagination links={pendingRequests.links} />
                            </>
                        ) : (
                            <div className="px-6 py-12 text-center">
                                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <svg
                                        className="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.8"
                                    >
                                        <path d="M8 7h8M8 11h8M8 15h5" />
                                        <rect
                                            x="4"
                                            y="3"
                                            width="16"
                                            height="18"
                                            rx="2"
                                        />
                                    </svg>
                                </div>

                                <h3 className="font-semibold text-gray-800">
                                    Tidak Ada Permintaan Baru
                                </h3>

                                <p className="mt-1 text-sm text-gray-500">
                                    Belum ada dosen yang menunggu persetujuan Admin.
                                </p>
                            </div>
                        )}
                    </section>

                    {/* DAFTAR DPP AKTIF */}
                    <section className="space-y-4">
                        <div>
                            <h2 className="text-lg font-bold text-gray-900">
                                Akun DPP
                            </h2>

                            <p className="mt-1 text-sm text-gray-500">
                                Daftar dosen yang sudah mendapatkan persetujuan Admin.
                            </p>
                        </div>

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
                                    <option value="">
                                        Semua Program Studi
                                    </option>

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
                                            <th className="px-6 py-4">Identitas</th>
                                            <th className="px-6 py-4">Program Studi</th>
                                            <th className="px-6 py-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody className="divide-y divide-gray-200">
                                        {activeDpps.length > 0 ? (
                                            activeDpps.map((item, index) => (
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
                                                        {item.email || 'Login melalui SIKAD'}
                                                    </td>

                                                    <td className="px-6 py-4">
                                                        <div className="font-medium text-gray-800">
                                                            {item.prodi || '-'}
                                                        </div>

                                                        <div className="text-xs text-gray-500">
                                                            {item.fakultas || item.kode_prodi || ''}
                                                        </div>
                                                    </td>

                                                    <td className="whitespace-nowrap px-6 py-4 text-right">
                                                        <DangerButton
                                                            type="button"
                                                            onClick={() => handleDelete(item)}
                                                        >
                                                            Hapus Akun
                                                        </DangerButton>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td
                                                    colSpan={5}
                                                    className="px-6 py-10 text-center text-gray-500"
                                                >
                                                    Belum ada akun DPP yang ditemukan.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination links={dpps.links} />
                        </section>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
