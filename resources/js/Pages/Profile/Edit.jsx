import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, mahasiswaProfile }) {
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
                        <section className="portal-profile-card space-y-4 bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <div>
                                <h3 className="text-lg font-medium text-gray-900">
                                    Data Mahasiswa
                                </h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Informasi ini juga digunakan pada daftar admin dan DPP.
                                </p>
                            </div>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">
                                        Email Mahasiswa
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {mahasiswaProfile.email || 'Belum diisi'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">
                                        Angkatan
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {mahasiswaProfile.angkatan || 'Belum diisi'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">
                                        No. Telepon
                                    </dt>
                                    <dd className="mt-1 text-sm text-gray-900">
                                        {mahasiswaProfile.no_telepon || 'Belum diisi'}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    )}

                    <div className="portal-profile-card bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="portal-profile-card portal-profile-danger bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
