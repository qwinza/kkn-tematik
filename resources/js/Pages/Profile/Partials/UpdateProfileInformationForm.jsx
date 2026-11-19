import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    mahasiswaProfile,
    className = '',
}) {
    const user = usePage().props.auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
        });

    const submit = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">
                    Informasi Profil
                </h2>

                <p className="mt-1 text-sm text-gray-600">
                    {mahasiswaProfile
                        ? 'Informasi identitas, akademik, dan akun Anda.'
                        : 'Perbarui nama dan alamat email akun Anda.'}
                </p>
            </header>

            <form onSubmit={submit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Nama lengkap" />

                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        isFocused
                        autoComplete="name"
                        disabled
                    />
                    <InputError className="mt-2" message={errors.name} />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Alamat email" />

                    <TextInput
                        id="email"
                        type="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="username"
                        disabled
                    />

                    <InputError className="mt-2" message={errors.email} />
                </div>

                {mustVerifyEmail && user.email_verified_at === null && (
                    <div>
                        <p className="mt-2 text-sm text-gray-800">
                            Your email address is unverified.
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        {status === 'verification-link-sent' && (
                            <div className="mt-2 text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email address.
                            </div>
                        )}
                    </div>
                )}
            </form>

            {mahasiswaProfile && (
                <dl className="mt-8 grid grid-cols-1 gap-4 border-t border-gray-200 pt-6 sm:grid-cols-2">
                    {[
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
            )}
        </section>
    );
}
