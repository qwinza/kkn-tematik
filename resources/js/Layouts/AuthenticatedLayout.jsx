import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm';
import SweetAlertNotifications from '@/Components/SweetAlertNotifications';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children, showSweetAlerts = true }) {
    const pageProps = usePage().props;
    const user = pageProps.auth.user;
    const flash = pageProps.flash || {};
    const activityNotifications = pageProps.activityNotifications || [];
    const userInitials = user.name
        .split(/\s+/)
        .map((namePart) => namePart.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase();
    const userRoleLabel = user.is_admin ? 'Admin' : 'Mahasiswa';
    const todayLabel = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
    const adminNavigationItems = [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            active: route().current('dashboard'),
        },
        {
            label: 'Mahasiswa',
            href: route('admin.mahasiswa.index'),
            active: route().current('admin.mahasiswa.*'),
        },
        {
            label: 'Kegiatan',
            href: route('log-kegiatan.index'),
            active: route().current('log-kegiatan.*'),
        },
        {
            label: 'Jadwal Kegiatan',
            href: route('master-kegiatan.index'),
            active: route().current('master-kegiatan.*'),
        },
        {
            label: 'Analitik',
            href: route('admin.analytics'),
            active: route().current('admin.analytics'),
        },
    ];

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);
    const [showingNotifications, setShowingNotifications] = useState(false);

    return (
        <div className="portal-shell min-h-screen bg-gray-100">
            {showSweetAlerts && <SweetAlertNotifications />}
            <nav className="portal-navbar border-b border-gray-100 bg-white">
                <div className="portal-navbar-container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="portal-navbar-row flex h-16 justify-between">
                        <div className="portal-navbar-brand-group flex">
                            <div className="portal-navbar-brand flex shrink-0 items-center">
                                <Link href="/">
                                    <img
                                        className="portal-brand-wordmark"
                                        src="/images/itenasresik.jpeg"
                                        alt="ITENAS RESIK"
                                    />
                                </Link>
                            </div>

                        </div>

                        <div className="portal-navigation hidden lg:flex">
                            {user?.is_admin ? (
                                <div className="portal-admin-navigation">
                                    {adminNavigationItems.map((item) =>
                                        item.disabled ? (
                                            <span
                                                key={item.label}
                                                className="portal-admin-nav-item is-disabled"
                                                aria-disabled="true"
                                                title="Halaman belum tersedia"
                                            >
                                                {item.label}
                                            </span>
                                        ) : (
                                            <Link
                                                key={item.label}
                                                href={item.href}
                                                className={`portal-admin-nav-item${item.active ? ' is-active' : ''}`}
                                                aria-current={item.active ? 'page' : undefined}
                                            >
                                                {item.label}
                                            </Link>
                                        )
                                    )}
                                </div>
                            ) : (
                                <div className="portal-student-navigation">
                                    <NavLink
                                        href={route('dashboard')}
                                        active={route().current('dashboard')}
                                    >
                                        Dashboard
                                    </NavLink>

                                    <NavLink
                                        href={route('log-kegiatan.create')}
                                        active={route().current('log-kegiatan.create') || route().current('log-kegiatan.edit')}
                                    >
                                        Tambah Kegiatan
                                    </NavLink>

                                    <NavLink
                                        href={route('log-kegiatan.index')}
                                        active={
                                            route().current('log-kegiatan.index') ||
                                            route().current('log-kegiatan.show')
                                        }
                                    >
                                        Kegiatan
                                    </NavLink>

                                    {user?.is_mahasiswa && (
                                        <NavLink
                                            href={route('mahasiswa.schedule.index')}
                                            active={route().current('mahasiswa.schedule.*')}
                                        >
                                            Riwayat Kehadiran
                                        </NavLink>
                                    )}
                                </div>
                            )}
                        </div>

                        <div className="portal-actions hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="portal-today">
                                <span>HARI INI</span>
                                <time dateTime={new Date().toISOString().slice(0, 10)}>
                                    {todayLabel}
                                </time>
                            </div>
                            {user?.is_mahasiswa && (
                                <div className="portal-notifications relative">
                                    <button
                                        type="button"
                                        onClick={() => setShowingNotifications((isShowing) => !isShowing)}
                                        aria-label="Notifikasi log kegiatan"
                                        aria-expanded={showingNotifications}
                                        className="portal-notification-trigger relative rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                    >
                                        <svg className="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 00-5-5.9V4a1 1 0 00-2 0v1.1A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9" />
                                        </svg>
                                        {activityNotifications.length > 0 && (
                                            <span className="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white" />
                                        )}
                                    </button>
                                    {showingNotifications && (
                                        <div className="portal-notification-menu absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-md bg-white shadow-lg ring-1 ring-black/5">
                                            <div className="border-b px-4 py-3 text-sm font-semibold text-gray-800">Notifikasi Kegiatan</div>
                                            {activityNotifications.length === 0 ? (
                                                <p className="px-4 py-5 text-sm text-gray-500">Belum ada pembaruan status log kegiatan.</p>
                                            ) : (
                                                <div className="max-h-80 overflow-y-auto">
                                                    {activityNotifications.map((notification) => (
                                                        <Link
                                                            key={notification.id}
                                                            href={route('log-kegiatan.show', notification.route_key)}
                                                            onClick={() => setShowingNotifications(false)}
                                                            className="block border-b px-4 py-3 hover:bg-gray-50"
                                                        >
                                                            <span className={`text-xs font-semibold ${notification.status === 'disetujui' ? 'text-green-700' : 'text-red-700'}`}>
                                                                {notification.status === 'disetujui' ? 'Log disetujui' : 'Log ditolak'}
                                                            </span>
                                                            <span className="mt-1 block truncate text-sm text-gray-700">{notification.kegiatan}</span>
                                                        </Link>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}
                            <div className="portal-profile-menu relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="portal-user-trigger inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                <span className="portal-user-avatar">{userInitials}</span>
                                                <span className="portal-user-copy">
                                                    <strong>{user.name}</strong>
                                                    <small>{userRoleLabel}</small>
                                                </span>
                                                <svg
                                                    className="portal-user-chevron -me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content contentClasses="portal-user-panel">
                                        <div className="portal-user-panel-heading">
                                            <div>
                                                <span>{user.is_admin ? 'AKUN ADMIN' : 'AKUN MAHASISWA'}</span>
                                                <strong>Profil & Pengaturan</strong>
                                            </div>
                                            <button
                                                className="portal-user-panel-close"
                                                type="button"
                                                aria-label="Tutup profil"
                                            >
                                                ×
                                            </button>
                                        </div>
                                        <div className="portal-user-summary">
                                            <span className="portal-user-avatar portal-user-avatar-large">
                                                {userInitials}
                                            </span>
                                            <span className="portal-user-summary-copy">
                                                <strong>{user.name}</strong>
                                                <small>{user.nim || user.email}</small>
                                                <em>{userRoleLabel}</em>
                                            </span>
                                        </div>
                                        <div
                                            className="portal-user-profile-form-wrap"
                                            onClick={(event) => event.stopPropagation()}
                                        >
                                            <UpdateProfileInformationForm
                                                mustVerifyEmail={false}
                                                status={null}
                                                className="portal-user-profile-form"
                                            />
                                        </div>
                                        <div className="portal-user-panel-links">
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                            className="portal-profile-edit-link"
                                        >
                                            Pengaturan lanjutan
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="portal-profile-logout-link"
                                        >
                                            Keluar
                                        </Dropdown.Link>
                                        </div>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="portal-mobile-toggle -me-2 flex items-center lg:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' portal-mobile-navigation lg:hidden'
                    }
                >
                    <div className="portal-mobile-navigation-links space-y-1 pb-3 pt-2">
                        {user?.is_admin ? adminNavigationItems.map((item) => item.disabled ? (
                            <span
                                key={item.label}
                                className="portal-mobile-admin-item is-disabled"
                                aria-disabled="true"
                            >
                                {item.label}
                            </span>
                        ) : (
                            <Link
                                key={item.label}
                                href={item.href}
                                className={`portal-mobile-admin-item${item.active ? ' is-active' : ''}`}
                                aria-current={item.active ? 'page' : undefined}
                            >
                                {item.label}
                            </Link>
                        )) : (
                            <>
                                <ResponsiveNavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Dashboard
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('log-kegiatan.create')}
                                    active={route().current('log-kegiatan.create') || route().current('log-kegiatan.edit')}
                                >
                                    Tambah Kegiatan
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('log-kegiatan.index')}
                                    active={route().current('log-kegiatan.index') || route().current('log-kegiatan.show')}
                                >
                                    Kegiatan
                                </ResponsiveNavLink>
                            </>
                        )}
                        {user?.is_mahasiswa && (
                            <ResponsiveNavLink
                                href={route('mahasiswa.schedule.index')}
                                active={route().current('mahasiswa.schedule.*')}
                            >
                                Riwayat Kehadiran
                            </ResponsiveNavLink>
                        )}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="portal-page-heading bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {/* Flash messages are presented by SweetAlertNotifications. */}
            <div className="hidden">
                {flash.success && (
                    <div className="mb-4 rounded-md bg-green-50 p-4 border border-green-200">
                        <div className="flex">
                            <div className="shrink-0">
                                <svg className="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ms-3 text-sm font-medium text-green-800">
                                {flash.success}
                            </div>
                        </div>
                    </div>
                )}

                {flash.error && (
                    <div className="mb-4 rounded-md bg-red-50 p-4 border border-red-200">
                        <div className="flex">
                            <div className="shrink-0">
                                <svg className="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ms-3 text-sm font-medium text-red-800">
                                {flash.error}
                            </div>
                        </div>
                    </div>
                )}
            </div>

            <main className="portal-main">{children}</main>
        </div>
    );
}
