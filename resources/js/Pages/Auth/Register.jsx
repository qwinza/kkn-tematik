import SweetAlertNotifications from '@/Components/SweetAlertNotifications';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Register({ prodis }) {
    const { data, setData, post, processing, errors } = useForm({
        nama_lengkap: '',
        nim: '',
        email: '',
        kode_prodi: '',
    });

    function submit(event) {
        event.preventDefault();
        post(route('register'));
    }

    const fields = [
        {
            id: 'nim',
            label: 'NRP',
            type: 'text',
            placeholder: 'Masukkan NRP',
            inputMode: 'numeric',
            autoComplete: 'username',
            icon: <rect x="4" y="4" width="16" height="16" rx="2" />,
        },
        {
            id: 'nama_lengkap',
            label: 'Nama Lengkap',
            type: 'text',
            placeholder: 'Masukkan nama lengkap',
            autoComplete: 'name',
            icon: <><circle cx="12" cy="8" r="3" /><path d="M5 20a7 7 0 0 1 14 0" /></>,
        },
        {
            id: 'email',
            label: 'Email',
            type: 'email',
            placeholder: 'Masukkan email aktif',
            autoComplete: 'email',
            icon: <><rect x="3.5" y="5" width="17" height="14" rx="2" /><path d="m4.5 7 7.5 6 7.5-6" /></>,
        },
    ];

    return (
        <>
            <Head title="Daftar Mahasiswa" />
            <SweetAlertNotifications />

            <main className="login-page">
                <img
                    className="login-emblem-watermark"
                    src="/images/itenas-emblem.svg"
                    alt=""
                    aria-hidden="true"
                />
                <section className="login-card" aria-label="Daftar akun mahasiswa">
                    <div className="login-form-side">
                        <div className="login-form-wrapper">
                            <div className="login-mobile-brand">
                                <img
                                    className="login-itenas-wordmark"
                                    src="/images/itenas-wordmark.svg"
                                    alt="Institut Teknologi Nasional"
                                />
                            </div>

                            <header className="login-heading">
                                <h1>Daftar Akun Mahasiswa</h1>
                                <p>Lengkapi identitas mahasiswa untuk membuat akun.</p>
                            </header>

                            <form className="login-form" onSubmit={submit}>
                                {fields.map((field) => (
                                    <div className="login-field" key={field.id}>
                                        <label htmlFor={field.id}>{field.label}</label>
                                        <div className={`login-input-wrapper ${errors[field.id] ? 'has-error' : ''}`}>
                                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                {field.icon}
                                            </svg>
                                            <input
                                                id={field.id}
                                                name={field.id}
                                                type={field.type}
                                                inputMode={field.inputMode}
                                                value={data[field.id]}
                                                placeholder={field.placeholder}
                                                autoComplete={field.autoComplete}
                                                aria-invalid={Boolean(errors[field.id])}
                                                onChange={(event) => setData(field.id, event.target.value)}
                                            />
                                        </div>
                                        {errors[field.id] && (
                                            <div className="login-error" id={`${field.id}-error`}>
                                                {errors[field.id]}
                                            </div>
                                        )}
                                    </div>
                                ))}

                                <div className="login-field">
                                    <label htmlFor="kode_prodi">Program Studi</label>
                                    <div className={`login-input-wrapper ${errors.kode_prodi ? 'has-error' : ''}`}>
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M3 9 12 4l9 5-9 5-9-5Z" />
                                            <path d="M7 11.5V16l5 3 5-3v-4.5" />
                                        </svg>
                                        <select
                                            id="kode_prodi"
                                            name="kode_prodi"
                                            value={data.kode_prodi}
                                            aria-invalid={Boolean(errors.kode_prodi)}
                                            onChange={(event) => setData('kode_prodi', event.target.value)}
                                            required
                                        >
                                            <option value="" disabled>Pilih program studi</option>
                                            {prodis.map((prodi) => (
                                                <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                                    {prodi.nama_prodi} ({prodi.fakultas})
                                                </option>
                                            ))}
                                        </select>
                                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m7 10 5 5 5-5" />
                                        </svg>
                                    </div>
                                    {errors.kode_prodi && (
                                        <div className="login-error" id="kode_prodi-error">
                                            {errors.kode_prodi}
                                        </div>
                                    )}
                                </div>

                                <button
                                    className="login-submit"
                                    type="submit"
                                    disabled={processing || prodis.length === 0}
                                    aria-busy={processing}
                                >
                                    {processing ? 'Memproses...' : 'Buat Akun'}
                                </button>
                            </form>

                            <p className="login-register-link">
                                Sudah punya akun?{' '}
                                <Link href={route('login')}>Masuk</Link>
                            </p>

                            <div className="login-footer">
                                <span>© 2026 KKN Tematik. All rights reserved.</span>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
