import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard({ summary = { totalKegiatan: 0, totalDisetujui: 0 } }) {
    const { auth } = usePage().props;
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-10">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-xl bg-white p-6 shadow-sm sm:p-8">
                        <p className="text-sm font-medium text-indigo-600">Dashboard Mahasiswa</p>
                        <h1 className="mt-2 text-2xl font-bold text-gray-900">Halo, {auth.user.name}</h1>
                        <p className="mt-2 text-sm text-gray-500">Pantau laporan kegiatan KKN dan status persetujuannya di sini.</p>
                    </section>
                    <section className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-xl bg-white p-6 shadow-sm">
                            <p className="text-sm text-gray-500">Total Kegiatan</p>
                            <p className="mt-2 text-3xl font-bold text-gray-900">{summary.totalKegiatan}</p>
                        </div>
                        <div className="rounded-xl bg-white p-6 shadow-sm">
                            <p className="text-sm text-gray-500">Total Disetujui</p>
                            <p className="mt-2 text-3xl font-bold text-green-700">{summary.totalDisetujui}</p>
                        </div>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
