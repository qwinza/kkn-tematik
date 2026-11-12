import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SortableHeading from '@/Components/SortableHeading';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import confirmWithSweetAlert from '@/Components/confirmWithSweetAlert';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ mahasiswas, prodis, filters, totalCount }) {
    const [search, setSearch] = useState(filters.search || '');
    const [kodeProdi, setKodeProdi] = useState(filters.kode_prodi || '');
    const filterTimer = useRef(null);
    const [modalState, setModalState] = useState({
    isOpen: false,
    mode: 'create',
    item: null,
    });
    const [openActionMenu, setOpenActionMenu] = useState(null);

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
        nama_lengkap: '',
        nim: '',
        email: '',
        kode_prodi: '',
    });

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
            nama_lengkap: item.nama_lengkap || item.user?.name || '',
            nim: item.nim || item.user?.nim || '',
            email: item.user?.email || '',
            kode_prodi: item.kode_prodi || '',
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

    const handleSubmit = async (e) => {
        e.preventDefault();

        const confirmed = await confirmWithSweetAlert({
            title: modalState.mode === 'create' ? 'Tambah mahasiswa?' : 'Simpan perubahan?',
            text: modalState.mode === 'create'
                ? 'Data mahasiswa dan akun login akan dibuat.'
                : 'Perubahan data mahasiswa akan disimpan.',
            icon: 'question',
            confirmButtonText: modalState.mode === 'create' ? 'Ya, tambah' : 'Ya, simpan',
        });

        if (!confirmed) return;

        if (modalState.mode === 'create') {
            post(route('admin.mahasiswa.store'), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
        } else {
            put(
                route('admin.mahasiswa.update', modalState.item.route_key),
                {
                    preserveScroll: true,
                    onSuccess: closeModal,
                }
            );
        }
    };

    const handleDelete = async (item) => {
        const confirmed = await confirmWithSweetAlert({
            title: 'Hapus mahasiswa?',
            text: `Akun ${item.nama_lengkap} akan dihapus. Penghapusan akan ditolak jika mahasiswa memiliki riwayat kegiatan atau rencana kehadiran.`,
            confirmButtonText: 'Ya, hapus',
        });

        if (!confirmed) return;

        router.delete(
            route('admin.mahasiswa.destroy', item.route_key),
            { preserveScroll: true }
        );
    };

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

                        <div className="flex items-center gap-3">
                            <span className="portal-admin-students-count">
                                Total Mahasiswa Terdaftar: <strong>{totalCount}</strong>
                            </span>

                            <PrimaryButton onClick={openCreateModal}>
                                + Tambah Mahasiswa
                            </PrimaryButton>
                        </div>
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
                                                        <div className="portal-admin-students-action-menu">
                                                            <button
                                                                type="button"
                                                                className="portal-admin-students-menu-toggle"
                                                                aria-label={`Buka aksi untuk ${mhs.nama_lengkap}`}
                                                                aria-haspopup="menu"
                                                                aria-expanded={openActionMenu === mhs.id}
                                                                onClick={() => setOpenActionMenu(openActionMenu === mhs.id ? null : mhs.id)}
                                                            >
                                                                <svg viewBox="0 0 20 20" aria-hidden="true" className="portal-admin-students-menu-icon">
                                                                    <path d="M3 5h14M3 10h14M3 15h14" />
                                                                </svg>
                                                            </button>
                                                            {openActionMenu === mhs.id && (
                                                                <div className="portal-admin-students-menu" role="menu">
                                                                    <Link
                                                                        href={route('admin.mahasiswa.show', mhs.route_key)}
                                                                        className="portal-admin-students-menu-item"
                                                                        role="menuitem"
                                                                        onClick={() => setOpenActionMenu(null)}
                                                                    >Detail</Link>
                                                                    <button
                                                                        type="button"
                                                                        className="portal-admin-students-menu-item"
                                                                        role="menuitem"
                                                                        onClick={() => {
                                                                            setOpenActionMenu(null);
                                                                            openEditModal(mhs);
                                                                        }}
                                                                    >Edit</button>
                                                                    <button
                                                                        type="button"
                                                                        className="portal-admin-students-menu-item is-danger"
                                                                        role="menuitem"
                                                                        onClick={() => {
                                                                            setOpenActionMenu(null);
                                                                            handleDelete(mhs);
                                                                        }}
                                                                    >Hapus</button>
                                                                </div>
                                                            )}
                                                        </div>
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
            <Modal
                show={modalState.isOpen}
                onClose={closeModal}
                maxWidth="xl"
            >
                <form onSubmit={handleSubmit} className="p-6">
                    <h2 className="text-lg font-semibold text-gray-900">
                        {modalState.mode === 'create'
                            ? 'Tambah Mahasiswa'
                            : 'Edit Data Mahasiswa'}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        {modalState.mode === 'create'
                            ? 'Masukkan identitas mahasiswa yang mengikuti KKN Tematik.'
                            : 'Perbarui identitas dan program studi mahasiswa.'}
                    </p>

                    <div className="mt-6 space-y-4">
                        <div>
                            <InputLabel htmlFor="nama_lengkap" value="Nama Lengkap" />
                            <TextInput
                                id="nama_lengkap"
                                className="mt-1 block w-full"
                                value={data.nama_lengkap}
                                onChange={(e) => setData('nama_lengkap', e.target.value)}
                                required
                            />
                            <InputError message={errors.nama_lengkap} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="nim" value="NIM" />
                            <TextInput
                                id="nim"
                                type="text"
                                inputMode="numeric"
                                className="mt-1 block w-full"
                                value={data.nim}
                                onChange={(e) => setData('nim', e.target.value)}
                                required
                            />
                            <InputError message={errors.nim} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="email" value="Email (Opsional)" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} className="mt-1" />
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
                                <option value="">Pilih Program Studi</option>
                                {prodis.map((prodi) => (
                                    <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                        {prodi.kode_prodi} - {prodi.nama_prodi}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.kode_prodi} className="mt-1" />
                        </div>

                    </div>

                    <div className="mt-6 flex justify-end gap-3">
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
        </AuthenticatedLayout>
    );
}
