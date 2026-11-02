import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ mahasiswas, programStudis, filters, totalCount }) {
    const [search, setSearch] = useState(filters.search || '');
    const [programStudiId, setProgramStudiId] = useState(filters.program_studi_id || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('admin.mahasiswa.index'),
            {
                search: search || undefined,
                program_studi_id: programStudiId || undefined,
            },
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const handleReset = () => {
        setSearch('');
        setProgramStudiId('');
        router.get(
            route('admin.mahasiswa.index'),
            {},
            {
                preserveState: true,
                replace: true,
            }
        );
    };

    const isFiltered = Boolean(filters.search || filters.program_studi_id);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Monitoring Mahasiswa
                    </h2>
                    <div className="text-sm text-gray-500">
                        Total Mahasiswa Terdaftar:{' '}
                        <span className="font-semibold text-gray-800">{totalCount}</span>
                    </div>
                </div>
            }
        >
            <Head title="Monitoring Mahasiswa" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Filter & Search Bar */}
                    <div className="mb-6 rounded-lg bg-white p-5 shadow-sm">
                        <form
                            onSubmit={handleSearch}
                            className="flex flex-col gap-4 md:flex-row md:items-end"
                        >
                            {/* Input Pencarian */}
                            <div className="flex-1">
                                <label
                                    htmlFor="search"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Cari Nama atau NIM
                                </label>
                                <input
                                    id="search"
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Contoh: Faris atau 152024038..."
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>

                            {/* Dropdown Program Studi */}
                            <div className="w-full md:w-72">
                                <label
                                    htmlFor="program_studi_id"
                                    className="block text-sm font-medium text-gray-700"
                                >
                                    Filter Program Studi
                                </label>
                                <select
                                    id="program_studi_id"
                                    value={programStudiId}
                                    onChange={(e) => setProgramStudiId(e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">Semua Program Studi</option>
                                    {programStudis.map((prodi) => (
                                        <option key={prodi.id} value={prodi.id}>
                                            {prodi.nama_prodi} ({prodi.fakultas})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Tombol Aksi */}
                            <div className="flex items-center gap-2">
                                <button
                                    type="submit"
                                    className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    Cari
                                </button>
                                {isFiltered && (
                                    <button
                                        type="button"
                                        onClick={handleReset}
                                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Reset Filter
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>

                    {/* Tabel Mahasiswa */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            No.
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Nama Lengkap
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            NIM
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Program Studi
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Fakultas
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {mahasiswas.data.length > 0 ? (
                                        mahasiswas.data.map((mhs, index) => {
                                            const rowNumber =
                                                (mahasiswas.current_page - 1) *
                                                    mahasiswas.per_page +
                                                index +
                                                1;
                                            const prodiNama =
                                                mhs.program_studi_detail?.nama_prodi ||
                                                mhs.program_studi ||
                                                '-';
                                            const fakultas =
                                                mhs.program_studi_detail?.fakultas || '-';

                                            return (
                                                <tr
                                                    key={mhs.id}
                                                    className="hover:bg-gray-50 transition duration-150"
                                                >
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {mhs.nama_lengkap || mhs.user?.name}
                                                        </div>
                                                        {mhs.user?.email && (
                                                            <div className="text-xs text-gray-500">
                                                                {mhs.user.email}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-mono text-gray-600">
                                                        {mhs.nim || mhs.user?.nim || '-'}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-800">
                                                        {prodiNama}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                        <span className="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                                            {fakultas}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-center text-sm font-medium">
                                                        <Link
                                                            href={route(
                                                                'admin.mahasiswa.show',
                                                                mhs.id
                                                            )}
                                                            className="inline-flex items-center rounded bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                                        >
                                                            Lihat Detail
                                                        </Link>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-6 py-10 text-center text-sm text-gray-500"
                                            >
                                                Tidak ada data mahasiswa yang ditemukan.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {mahasiswas.links && mahasiswas.links.length > 3 && (
                            <div className="flex flex-col items-center justify-between border-t border-gray-200 px-4 py-3 sm:flex-row sm:px-6">
                                <div className="mb-2 text-sm text-gray-600 sm:mb-0">
                                    Menampilkan{' '}
                                    <span className="font-semibold">{mahasiswas.from || 0}</span>{' '}
                                    sampai{' '}
                                    <span className="font-semibold">{mahasiswas.to || 0}</span>{' '}
                                    dari{' '}
                                    <span className="font-semibold">{mahasiswas.total}</span> hasil
                                </div>
                                <div className="flex flex-wrap gap-1">
                                    {mahasiswas.links.map((link, idx) => {
                                        if (!link.url) {
                                            return (
                                                <span
                                                    key={idx}
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                    className="rounded border border-gray-200 px-3 py-1 text-xs text-gray-400"
                                                />
                                            );
                                        }

                                        return (
                                            <Link
                                                key={idx}
                                                href={link.url}
                                                preserveState
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                                className={`rounded border px-3 py-1 text-xs transition duration-150 ${
                                                    link.active
                                                        ? 'border-indigo-600 bg-indigo-600 text-white font-semibold'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                                }`}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
