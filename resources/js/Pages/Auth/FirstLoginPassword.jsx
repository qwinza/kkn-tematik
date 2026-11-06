import { Head, useForm } from '@inertiajs/react';

export default function FirstLoginPassword() {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.put('/first-login/password');
    };

    return (
        <>
            <Head title="Ganti Password Pertama Kali" />

            <main className="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-10">
                <section className="w-full max-w-md rounded-2xl bg-white p-6 shadow-sm sm:p-8">
                    <div className="mb-6">
                        <p className="text-sm font-semibold text-red-700">KKN TEMATIK ITENAS</p>
                        <h1 className="mt-2 text-2xl font-bold text-gray-900">
                            Ganti Password
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-gray-600">
                            Demi keamanan akun, ganti password sementara sebelum melanjutkan ke aplikasi.
                        </p>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <label htmlFor="current_password" className="mb-1 block text-sm font-medium text-gray-700">
                                Password sementara
                            </label>
                            <input
                                id="current_password"
                                type="password"
                                autoComplete="current-password"
                                value={form.data.current_password}
                                onChange={(event) => form.setData('current_password', event.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            />
                            {form.errors.current_password && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.current_password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-1 block text-sm font-medium text-gray-700">
                                Password baru
                            </label>
                            <input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            />
                            {form.errors.password && (
                                <p className="mt-1 text-sm text-red-600">{form.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="mb-1 block text-sm font-medium text-gray-700">
                                Konfirmasi password baru
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100"
                                required
                            />
                        </div>

                        <p className="text-xs leading-5 text-gray-500">
                            Gunakan password baru yang berbeda dari password sementara.
                        </p>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="w-full rounded-lg bg-red-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {form.processing ? 'Menyimpan...' : 'Simpan Password Baru'}
                        </button>
                    </form>
                </section>
            </main>
        </>
    );
}
