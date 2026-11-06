import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Index({ peserta, rencanaKehadirans, sessions = [], today }) {
    const { flash } = usePage().props;

    const formatSessionLabel = (session) => {
        const match = session.match(/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})\s*\((.+)\)$/);
        if (match) {
            const start = match[1].replace(':', '.');
            const end = match[2].replace(':', '.');
            const sessionName = match[3];
            return `${sessionName} (${start} - ${end})`;
        }
        return session;
    };

    const { data, setData, post, processing, errors, reset } = useForm({
        tanggal_rencana: '',
        jam_kehadiran: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('mahasiswa.schedule.store'), {
            onSuccess: () => {
                reset();
            },
        });
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        // Mengantisipasi format ISO atau Y-m-d murni
        const datePart = dateString.includes('T') ? dateString.split('T')[0] : dateString;
        const [year, month, day] = datePart.split('-');
        const dateObj = new Date(year, parseInt(month, 10) - 1, day);

        return dateObj.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const prodiNama =
        peserta.program_studi_detail?.nama_prodi || peserta.program_studi || '-';
    const fakultas = peserta.program_studi_detail?.fakultas || '-';

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Jadwal Kegiatan
                    </h2>
                    <p className="text-xs text-gray-500 mt-1">
                        Pencatatan rencana tanggal dan sesi kehadiran kegiatan KKN Tematik
                    </p>
                </div>
            }
        >
            <Head title="Jadwal Kegiatan" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Pesan Sukses Flash */}
                    {flash?.success && (
                        <div className="rounded-md bg-green-50 p-4 border border-green-200 shadow-sm">
                            <div className="flex">
                                <div className="shrink-0">
                                    <svg
                                        className="h-5 w-5 text-green-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fillRule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div className="ms-3">
                                    <p className="text-sm font-medium text-green-800">
                                        {flash.success}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Section A: Identitas Mahasiswa (Read-Only) */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b border-gray-100 bg-gray-50 px-6 py-4">
                            <h3 className="text-base font-semibold leading-6 text-gray-900">
                                Data Identitas Mahasiswa
                            </h3>
                            <p className="mt-0.5 text-xs text-gray-500">
                                Identitas diri dan program studi peserta KKN Tematik (Read-Only)
                            </p>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nama Lengkap
                                    </label>
                                    <div className="mt-1 text-sm font-semibold text-gray-900">
                                        {peserta.nama_lengkap || '-'}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nomor Induk Mahasiswa (NIM)
                                    </label>
                                    <div className="mt-1 font-mono text-sm font-semibold text-gray-900">
                                        {peserta.nim || '-'}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Program Studi
                                    </label>
                                    <div className="mt-1 text-sm font-semibold text-gray-900">
                                        {prodiNama}
                                    </div>
                                </div>

                                <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                    <label className="block text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Fakultas
                                    </label>
                                    <div className="mt-1">
                                        <span className="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                            {fakultas}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Section B: Atur Jadwal Kehadiran */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b border-gray-100 bg-gray-50 px-6 py-4">
                            <h3 className="text-base font-semibold leading-6 text-gray-900">
                                Atur Jadwal Kehadiran
                            </h3>
                            <p className="mt-0.5 text-xs text-gray-500">
                                Pilih tanggal dan sesi kehadiran yang akan dihadiri
                            </p>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Input Tanggal Rencana */}
                                <div>
                                    <label
                                        htmlFor="tanggal_rencana"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Tanggal Rencana <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="tanggal_rencana"
                                        type="date"
                                        min={today}
                                        value={data.tanggal_rencana}
                                        onChange={(e) =>
                                            setData('tanggal_rencana', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                    <InputError
                                        message={errors.tanggal_rencana}
                                        className="mt-1"
                                    />
                                </div>

                                {/* Pilihan Sesi Kehadiran */}
                                <div>
                                    <label
                                        htmlFor="jam_kehadiran"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        Sesi Kehadiran <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="jam_kehadiran"
                                        value={data.jam_kehadiran}
                                        onChange={(e) =>
                                            setData('jam_kehadiran', e.target.value)
                                        }
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        <option value="">-- Pilih Sesi Kehadiran --</option>
                                        {(sessions || []).map((session) => (
                                            <option
                                                key={session}
                                                value={session}
                                            >
                                                {formatSessionLabel(session)}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError
                                        message={errors.jam_kehadiran}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            {/* Tombol Submit */}
                            <div className="mt-6 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition duration-150"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Rencana Kehadiran'}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Section C: Rencana yang Sudah Dijadwalkan */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b border-gray-100 bg-gray-50 px-6 py-4">
                            <h3 className="text-base font-semibold leading-6 text-gray-900">
                                Rencana yang Sudah Dijadwalkan
                            </h3>
                            <p className="mt-0.5 text-xs text-gray-500">
                                Daftar tanggal dan sesi kegiatan yang telah Anda rencanakan.
                            </p>
                        </div>

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
                                            Tanggal Rencana
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Sesi Kehadiran
                                        </th>
                                        <th
                                            scope="col"
                                            className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                                        >
                                            Tanggal Dibuat
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {rencanaKehadirans.data.length > 0 ? (
                                        rencanaKehadirans.data.map((item, index) => {
                                            const rowNumber =
                                                (rencanaKehadirans.current_page - 1) *
                                                    rencanaKehadirans.per_page +
                                                index +
                                                1;

                                            return (
                                                <tr
                                                    key={item.id}
                                                    className="hover:bg-gray-50 transition duration-150"
                                                >
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                        {rowNumber}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                        {formatDate(item.tanggal_rencana)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                                        <span className="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 border border-indigo-200">
                                                            {item.jam_kehadiran}
                                                        </span>
                                                    </td>
                                                    <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                        {formatDateTime(item.created_at)}
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-12 text-center text-sm text-gray-500"
                                            >
                                                <div className="flex flex-col items-center justify-center space-y-2">
                                                    <svg
                                                        className="h-8 w-8 text-gray-400"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth="1.5"
                                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                        />
                                                    </svg>
                                                    <span>
                                                        Belum ada rencana kehadiran yang dibuat.
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {rencanaKehadirans.links && rencanaKehadirans.links.length > 3 && (
                            <div className="flex flex-col items-center justify-between border-t border-gray-200 px-4 py-3 sm:flex-row sm:px-6">
                                <div className="mb-2 text-sm text-gray-600 sm:mb-0">
                                    Menampilkan{' '}
                                    <span className="font-semibold">
                                        {rencanaKehadirans.from || 0}
                                    </span>{' '}
                                    sampai{' '}
                                    <span className="font-semibold">
                                        {rencanaKehadirans.to || 0}
                                    </span>{' '}
                                    dari{' '}
                                    <span className="font-semibold">
                                        {rencanaKehadirans.total}
                                    </span>{' '}
                                    hasil
                                </div>
                                <div className="flex flex-wrap gap-1">
                                    {rencanaKehadirans.links.map((link, idx) => {
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
