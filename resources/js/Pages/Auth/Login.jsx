
import { useState } from 'react';
import SweetAlertNotifications from '@/Components/SweetAlertNotifications';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status }) {
    const [showPassword, setShowPassword] = useState(false);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        identifier: '',
        password: '',
    });

    const identifierError = errors.identifier || errors.email;

    function submit(event) {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    }

    return (
        <>
            <Head title="Masuk" />
            <SweetAlertNotifications />

            <main className="login-page">
                <img
                    className="login-emblem-watermark"
                    src="/images/itenas-emblem.svg"
                    alt=""
                    aria-hidden="true"
                />
                <section
                    className="login-card"
                    aria-label="Masuk ke KKN Tematik"
                >
                    <div className="login-form-side">
                        <div className="login-form-wrapper">
                            <div className="login-mobile-brand size-md">
                                <img
                                    className="login-itenas-wordmark"
                                    src="/images/itenasresik-trim.png"
                                    alt="ITENAS RESIK"
                                />
                            </div>

                            <header className="login-heading">
                                <h1>
                                    Selamat Datang di Portal KKN ITENAS RESIK 2026
                                </h1>
                            </header>

                            {status && (
                                <div
                                    className="login-alert success"
                                    role="status"
                                >
                                    {status}
                                </div>
                            )}

                            <form
                                className="login-form"
                                onSubmit={submit}
                            >
                                {/* IDENTIFIER */}
                                <div className="login-field">
                                    <label htmlFor="identifier">
                                        NIM
                                    </label>

                                    <div
                                        className={`login-input-wrapper ${
                                            identifierError ? 'has-error' : ''
                                        }`}
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <rect
                                                x="3.5"
                                                y="5"
                                                width="17"
                                                height="14"
                                                rx="2"
                                            />
                                            <path d="m4.5 7 7.5 6 7.5-6" />
                                        </svg>

                                        <input
                                            id="identifier"
                                            name="identifier"
                                            type="text"
                                            value={data.identifier}
                                            placeholder="Masukkan NIM atau email"
                                            autoComplete="username"
                                            aria-invalid={Boolean(identifierError)}
                                            aria-describedby={
                                                identifierError
                                                    ? 'identifier-error'
                                                    : undefined
                                            }
                                            onChange={(event) =>
                                                setData(
                                                    'identifier',
                                                    event.target.value
                                                )
                                            }
                                        />
                                    </div>

                                    {identifierError && (
                                        <div
                                            className="login-error"
                                            id="identifier-error"
                                        >
                                            {identifierError}
                                        </div>
                                    )}
                                </div>

                                {/* PASSWORD */}
                                <div className="login-field">
                                    <div className="login-label-row">
                                        <label htmlFor="password">
                                            PIN Kampus
                                        </label>
                                    </div>

                                    <div
                                        className={`login-input-wrapper ${
                                            errors.password ? 'has-error' : ''
                                        }`}
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <rect
                                                x="5"
                                                y="10"
                                                width="14"
                                                height="11"
                                                rx="2"
                                            />
                                            <path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" />
                                        </svg>

                                        <input
                                            id="password"
                                            name="password"
                                            type={
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            value={data.password}
                                            placeholder="Masukkan PIN kampus"
                                            autoComplete="current-password"
                                            aria-invalid={Boolean(errors.password)}
                                            aria-describedby={
                                                errors.password
                                                    ? 'password-error'
                                                    : undefined
                                            }
                                            onChange={(event) =>
                                                setData(
                                                    'password',
                                                    event.target.value
                                                )
                                            }
                                        />

                                        <button
                                            type="button"
                                            className="password-toggle"
                                            onClick={() =>
                                                setShowPassword(!showPassword)
                                            }
                                            aria-label={
                                                showPassword
                                                    ? 'Sembunyikan password'
                                                    : 'Tampilkan password'
                                            }
                                        >
                                            {showPassword ? (
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c5.5 0 9 7 9 7a16 16 0 0 1-3.2 4.1M6.2 6.2C3.9 7.7 3 12 3 12s3.5 7 9 7c1.1 0 2.1-.3 3-.7" />
                                                </svg>
                                            ) : (
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M3 12s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7Z" />
                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="3"
                                                    />
                                                </svg>
                                            )}
                                        </button>
                                    </div>

                                    {errors.password && (
                                        <div
                                            className="login-error"
                                            id="password-error"
                                        >
                                            {errors.password}
                                        </div>
                                    )}
                                </div>

                                {/* LOGIN BUTTON */}
                                <button
                                    className="login-submit"
                                    type="submit"
                                    disabled={processing}
                                    aria-busy={processing}
                                >
                                    {processing ? (
                                        <>
                                            <span className="login-spinner" />
                                            Memproses...
                                        </>
                                    ) : (
                                        <>
                                            Masuk
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                aria-hidden="true"
                                            >
                                                <path d="M5 12h14M13 6l6 6-6 6" />
                                            </svg>
                                        </>
                                    )}
                                </button>
                            </form>

                            <div className="login-footer">
                                <span>
                                    © 2026 KKN Tematik. All rights reserved.
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
