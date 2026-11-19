
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
    isDpp,
    canManageLocalPassword,
    canDeleteAccount,
    mahasiswaProfile,
}) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="portal-profile-page py-12">
                <div className="portal-profile-content mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="portal-profile-card bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {mahasiswaProfile && (
                        <div className="portal-profile-card bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <section className="max-w-2xl">
                                <h2 className="text-lg font-medium text-gray-900">
                                    Data Mahasiswa
                                </h2>
                                <p className="mt-1 text-sm text-gray-600">
                                    Informasi identitas dan akademik yang tercatat untuk akun Anda.
                                </p>
                                <dl className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    {[
                                        ['Nama Lengkap', mahasiswaProfile.nama_lengkap],
                                        ['NIM', mahasiswaProfile.nim],
                                        ['Program Studi', mahasiswaProfile.program_studi],
                                        ['Fakultas', mahasiswaProfile.fakultas],
                                        ['Angkatan', mahasiswaProfile.angkatan],
                                        ['Nomor Telepon', mahasiswaProfile.no_telepon],
                                        ['Total Jam Disetujui', `${mahasiswaProfile.total_jam_disetujui ?? 0} jam`],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-lg bg-gray-50 p-4">
                                            <dt className="text-xs font-semibold uppercase tracking-wider text-gray-500">
                                                {label}
                                            </dt>
                                            <dd className="mt-1 text-sm font-medium text-gray-900">
                                                {value || '-'}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </section>
                        </div>
                    )}

                    {canManageLocalPassword && (
                        <div className="portal-profile-card bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <UpdatePasswordForm className="max-w-xl" />
                        </div>
                    )}

                    {isDpp && (
                        <div className="portal-profile-card bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <section className="max-w-xl">
                                <h2 className="text-lg font-medium text-gray-900">
                                    Pengelolaan Password
                                </h2>

                                <p className="mt-2 text-sm text-gray-600">
                                    Akun DPP menggunakan autentikasi SIKAD.
                                    Password Anda dikelola melalui layanan
                                    SIKAD ITENAS, bukan melalui aplikasi RESIK.
                                </p>
                            </section>
                        </div>
                    )}

                    {canDeleteAccount && (
                        <div className="portal-profile-card portal-profile-danger bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <DeleteUserForm className="max-w-xl" />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
