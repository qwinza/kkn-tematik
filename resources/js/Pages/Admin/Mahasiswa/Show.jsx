import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ mahasiswa }) {
    const prodiNama =
        mahasiswa.prodi?.nama_prodi || mahasiswa.program_studi || '-';
    const fakultas = mahasiswa.prodi?.fakultas || '-';
    const email = mahasiswa.user?.email || '-';
    const namaAkun = mahasiswa.user?.name || '-';

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Detail Mahasiswa
                        </h2>
                        <p className="text-xs text-gray-500 mt-0.5">
                            Informasi identitas dan profil akademik mahasiswa KKN
                        </p>
                    </div>
                    <Link
                        href={route('admin.mahasiswa.index')}
                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        &larr; Kembali ke Daftar
                    </Link>
                </div>
            }
        >
            <Head title={`Detail Mahasiswa - ${mahasiswa.nama_lengkap || namaAkun}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        {/* Header Profil */}
                        <div className="border-b border-gray-100 bg-gradient-to-r from-indigo-500 to-indigo-700 px-6 py-6 text-white">
                            <div className="flex items-center space-x-4">
                                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-indigo-700 font-bold text-xl shadow">
                                    {(mahasiswa.nama_lengkap || namaAkun)
                                        .charAt(0)
                                        .toUpperCase()}
                                </div>
                                <div>
                                    <h3 className="text-xl font-bold leading-6">
                                        {mahasiswa.nama_lengkap || namaAkun}
                                    </h3>
                                    <p className="mt-1 text-sm text-indigo-100 font-mono">
                                        NIM: {mahasiswa.nim || mahasiswa.user?.nim || '-'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Rincian Informasi */}
                        <div className="px-6 py-6">
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-6 sm:grid-cols-2">
                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nama Lengkap (Identitas)
                                    </dt>
                                    <dd className="mt-1 text-base font-medium text-gray-900">
                                        {mahasiswa.nama_lengkap || '-'}
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nomor Induk Mahasiswa (NIM)
                                    </dt>
                                    <dd className="mt-1 text-base font-mono font-medium text-gray-900">
                                        {mahasiswa.nim || mahasiswa.user?.nim || '-'}
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Program Studi
                                    </dt>
                                    <dd className="mt-1 text-base font-medium text-gray-900">
                                        {prodiNama}
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Fakultas
                                    </dt>
                                    <dd className="mt-1">
                                        <span className="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                                            {fakultas}
                                        </span>
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Nama Akun Sistem (User Name)
                                    </dt>
                                    <dd className="mt-1 text-base font-medium text-gray-900">
                                        {namaAkun}
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Email Akun
                                    </dt>
                                    <dd className="mt-1 text-base text-gray-900">
                                        {email}
                                    </dd>
                                </div>

                                <div className="rounded-lg bg-gray-50 p-4 sm:col-span-2">
                                    <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        Tanggal Registrasi Akun
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-700">
                                        {formatDate(mahasiswa.created_at)}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        {/* Footer Card */}
                        <div className="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end">
                            <Link
                                href={route('admin.mahasiswa.index')}
                                className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                &larr; Kembali ke Daftar Mahasiswa
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
