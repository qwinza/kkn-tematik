import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SortableHeading from '@/Components/SortableHeading';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ mahasiswas, prodis, filters, totalCount }) {
    const [search, setSearch] = useState(filters.search || '');
    const [kodeProdi, setKodeProdi] = useState(filters.kode_prodi || '');
    const filterTimer = useRef(null);

    useEffect(() => () => clearTimeout(filterTimer.current), []);

    const applyFilters = (nextSearch, nextKodeProdi, delay = 300) => {
        clearTimeout(filterTimer.current);
        filterTimer.current = setTimeout(() => {
            router.get(route('admin.mahasiswa.index'), {
                search: nextSearch || undefined,
                kode_prodi: nextKodeProdi || undefined,
                sort: filters.sort,
                direction: filters.direction,
            }, { preserveState: true, preserveScroll: true, replace: true });
        }, delay);
    };

    const handleSort = (column) => {
        clearTimeout(filterTimer.current);
        const direction = filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc';

        router.get(route('admin.mahasiswa.index'), {
            search: search || undefined,
            kode_prodi: kodeProdi || undefined,
            sort: column,
            direction,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const handleReset = () => {
        clearTimeout(filterTimer.current);
        setSearch('');
        setKodeProdi('');
        router.get(route('admin.mahasiswa.index'), {
            sort: filters.sort,
            direction: filters.direction,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const isFiltered = Boolean(search || kodeProdi);

    return (
        <AuthenticatedLayout>
            <Head title="Mahasiswa" />

            <div className="portal-admin-students">
                <div className="portal-admin-students-content">
                    <header className="portal-dashboard-page-heading portal-admin-students-heading">
                    <div>
                        <p className="portal-dashboard-breadcrumb">
                            Portal Admin <span>/</span> Mahasiswa
                        </p>
                        <h1>Mahasiswa</h1>
                        <p>Kelola data peserta dan program studi mahasiswa KKN.</p>
                    </div>
                    <span className="portal-admin-students-count">
                        Total Mahasiswa Terdaftar: <strong>{totalCount}</strong>
                    </span>
                    </header>

                    {/* Filter & Search Bar */}
                    <div className="portal-admin-students-filter">
                        <div className="portal-admin-students-filter-fields">
                            {/* Input Pencarian */}
                            <div className="portal-admin-students-search">
                                <label
                                    htmlFor="search"
                                >
                                    Cari Nama, NIM, atau Program Studi
                                </label>
                                <input
                                    id="search"
                                    type="text"
                                    value={search}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        setSearch(value);
                                        applyFilters(value, kodeProdi);
                                    }}
                                    placeholder="Cari nama, NIM, atau program studi..."
                                    className="portal-admin-students-input"
                                />
                            </div>

                            {/* Dropdown Program Studi */}
                            <div className="portal-admin-students-program-filter">
                                <label
                                    htmlFor="kode_prodi"
                                >
                                    Filter Program Studi
                                </label>
                                <select
                                    id="kode_prodi"
                                    value={kodeProdi}
                                    onChange={(e) => {
                                        const value = e.target.value;
                                        setKodeProdi(value);
                                        applyFilters(search, value, 0);
                                    }}
                                    className="portal-admin-students-input"
                                >
                                    <option value="">Semua Program Studi</option>
                                    {prodis.map((prodi) => (
                                        <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                            {prodi.nama_prodi} ({prodi.fakultas})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {isFiltered && (
                                <div className="portal-admin-students-reset-wrap">
                                    <button
                                        type="button"
                                        onClick={handleReset}
                                        className="portal-admin-students-reset"
                                    >
                                        Reset Filter
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Tabel Mahasiswa */}
                    <div className="portal-admin-students-table-card">
                        <div className="overflow-x-auto">
                            <table className="portal-admin-students-table">
                                <thead>
                                    <tr>
                                        <th
                                            scope="col"
                                        >
                                            No.
                                        </th>
                                        <SortableHeading label="Nama Lengkap" column="nama_lengkap" sort={filters.sort} direction={filters.direction} onSort={handleSort} />
                                        <SortableHeading label="NIM" column="nim" sort={filters.sort} direction={filters.direction} onSort={handleSort} />
                                        <SortableHeading label="Program Studi" column="prodi" sort={filters.sort} direction={filters.direction} onSort={handleSort} />
                                        <SortableHeading label="Fakultas" column="fakultas" sort={filters.sort} direction={filters.direction} onSort={handleSort} />
                                        <th
                                            scope="col"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mahasiswas.data.length > 0 ? (
                                        mahasiswas.data.map((mhs, index) => {
                                            const rowNumber =
                                                (mahasiswas.current_page - 1) *
                                                    mahasiswas.per_page +
                                                index +
                                                1;
                                            const prodiNama =
                                                mhs.prodi?.nama_prodi ||
                                                mhs.program_studi ||
                                                '-';
                                            const fakultas =
                                                mhs.prodi?.fakultas || '-';

                                            return (
                                                <tr
                                                    key={mhs.id}
                                                    className="portal-admin-students-row"
                                                >
                                                    <td className="portal-admin-students-number">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="portal-admin-students-name-cell">
                                                        <div className="portal-admin-students-name">
                                                            {mhs.nama_lengkap || mhs.user?.name}
                                                        </div>
                                                        {mhs.user?.email && (
                                                            <div className="portal-admin-students-email">
                                                                {mhs.user.email}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="portal-admin-students-nim">
                                                        {mhs.nim || mhs.user?.nim || '-'}
                                                    </td>
                                                    <td className="portal-admin-students-prodi">
                                                        {prodiNama}
                                                    </td>
                                                    <td className="portal-admin-students-faculty">
                                                        <span>
                                                            {fakultas}
                                                        </span>
                                                    </td>
                                                    <td className="portal-admin-students-actions">
                                                        <Link
                                                            href={route(
                                                                'admin.mahasiswa.show',
                                                                mhs.route_key
                                                            )}
                                                            className="portal-admin-students-detail"
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
                                                className="portal-admin-students-empty"
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
                            <div className="portal-admin-students-pagination">
                                <div>
                                    Menampilkan{' '}
                                    <span className="font-semibold">{mahasiswas.from || 0}</span>{' '}
                                    sampai{' '}
                                    <span className="font-semibold">{mahasiswas.to || 0}</span>{' '}
                                    dari{' '}
                                    <span className="font-semibold">{mahasiswas.total}</span> hasil
                                </div>
                                <div className="portal-admin-students-pagination-links">
                                    {mahasiswas.links.map((link, idx) => {
                                        if (!link.url) {
                                            return (
                                                <span
                                                    key={idx}
                                                    dangerouslySetInnerHTML={{
                                                        __html: link.label,
                                                    }}
                                                    className="is-disabled"
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
                                                className={`${
                                                    link.active
                                                        ? 'is-active'
                                                        : ''
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
