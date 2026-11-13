import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({
    summary = { totalKegiatan: 0, totalDisetujui: 0, totalLapangan: 0, totalKampus: 0, totalJamDisetujui: 0 },
}) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.is_admin;
    const approvalRate = summary.totalKegiatan > 0
        ? Math.round((summary.totalDisetujui / summary.totalKegiatan) * 100)
        : 0;
    const locationRate = (value) => (summary.totalKegiatan > 0
        ? Math.round((value / summary.totalKegiatan) * 100)
        : 0);
    const lapanganRate = locationRate(summary.totalLapangan ?? 0);
    const kampusRate = locationRate(summary.totalKampus ?? 0);
    const totalJam = Number(summary.totalJamDisetujui ?? 0);
    const totalJamLabel = Number.isInteger(totalJam) ? totalJam : totalJam.toFixed(1);
    const shortcuts = isAdmin
        ? [
            { label: 'Kegiatan', href: route('log-kegiatan.index'), icon: 'report' },
            { label: 'Mahasiswa', href: route('admin.mahasiswa.index'), icon: 'students' },
            { label: 'Jadwal Kegiatan', href: route('master-kegiatan.index'), icon: 'schedule' },
        ]
        : [
            { label: 'Tambah Kegiatan', href: route('log-kegiatan.create'), icon: 'attendance' },
            { label: 'Kegiatan', href: route('log-kegiatan.index'), icon: 'history' },
            { label: 'Jadwal Kegiatan', href: route('mahasiswa.schedule.index'), icon: 'schedule' },
        ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="portal-dashboard py-10">
                <div className="portal-dashboard-content mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="portal-dashboard-page-heading">
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal {isAdmin ? 'Admin' : 'Mahasiswa'} <span>/</span> Dashboard
                            </p>
                            <h1>Dashboard</h1>
                            <p>{isAdmin
                                ? 'Pantau kegiatan dan perkembangan mahasiswa KKN.'
                                : 'Pantau aktivitas lapangan dan perkembangan KKN Anda.'}
                            </p>
                        </div>
                    </header>

                    <section className="portal-dashboard-welcome">
                        <div className="portal-dashboard-welcome-copy">
                            <p>KKN ITENAS RESIK 2026</p>
                            <h2>Selamat Datang, {auth.user.name}!</h2>
                            <span>
                                {isAdmin
                                    ? 'Tinjau kegiatan dan pantau perkembangan mahasiswa.'
                                    : 'Setiap kegiatan yang Anda catat membantu memantau progres program.'}
                            </span>
                        </div>
                        <aside className="portal-dashboard-identity">
                            <span>{isAdmin ? 'AKUN' : 'NRP / NIM'}</span>
                            <strong>{isAdmin ? 'Admin KKN' : auth.user.nim || 'Mahasiswa'}</strong>
                            <small>{isAdmin ? 'Administrator' : 'Mahasiswa KKN 2026'}</small>
                        </aside>
                    </section>

                    <section className="portal-dashboard-overview">
                        <article className="portal-stat-card portal-stat-card-orange">
                            <span className="portal-stat-icon" aria-hidden="true">▤</span>
                            <p>Total Kegiatan</p>
                            <strong>{summary.totalKegiatan}</strong>
                            <small>kegiatan tercatat</small>
                        </article>
                        <article className="portal-stat-card portal-stat-card-success">
                            <span className="portal-stat-icon" aria-hidden="true">✓</span>
                            <p>Disetujui</p>
                            <strong>{summary.totalDisetujui}</strong>
                            <small>kegiatan terverifikasi</small>
                        </article>
                        <article className="portal-stat-card portal-stat-card-info">
                            <span className="portal-stat-icon" aria-hidden="true">◷</span>
                            <p>Total Jam Disetujui</p>
                            <strong>{totalJamLabel}</strong>
                            <small>jam kegiatan yang disetujui</small>
                        </article>
                        <article className="portal-dashboard-quick-card">
                            <div className="portal-dashboard-panel-kicker">AKSES CEPAT</div>
                            <h2>Menu</h2>
                            <p>Buka halaman yang sering Anda perlukan.</p>
                            <div className="portal-dashboard-quick-links">
                                {shortcuts.map((shortcut) => (
                                    <Link key={shortcut.label} href={shortcut.href}>
                                        <span aria-hidden="true">
                                            {shortcut.icon === 'history' ? '◷' : shortcut.icon === 'attendance' ? '⌖' : shortcut.icon === 'students' ? '♙' : shortcut.icon === 'schedule' ? '▦' : '▤'}
                                        </span>
                                        {shortcut.label}
                                    </Link>
                                ))}
                            </div>
                        </article>
                    </section>

                    <section className="portal-dashboard-bottom">
                        <article className="portal-dashboard-panel portal-dashboard-activity">
                            <div>
                                <p className="portal-dashboard-panel-kicker">AKTIVITAS</p>
                                <h2>Kegiatan</h2>
                            </div>
                            {summary.totalKegiatan > 0 ? (
                                <p>
                                    Terdapat {summary.totalKegiatan} kegiatan tercatat.
                                </p>
                            ) : (
                                <p>Belum ada kegiatan yang tercatat.</p>
                            )}
                            <Link href={route('log-kegiatan.index')}>
                                Lihat kegiatan
                                <span aria-hidden="true"> →</span>
                            </Link>
                        </article>

                        <article className="portal-dashboard-panel portal-dashboard-approval">
                            <div className="portal-dashboard-approval-heading">
                                <div>
                                    <p className="portal-dashboard-panel-kicker">PROGRES</p>
                                    <h2>Status Persetujuan</h2>
                                </div>
                                <strong>{approvalRate}%</strong>
                            </div>
                            <div className="portal-dashboard-progress-track">
                                <span style={{ width: `${approvalRate}%` }} />
                            </div>
                            <p>
                                {summary.totalDisetujui} dari {summary.totalKegiatan} kegiatan disetujui
                            </p>
                            <Link href={route('log-kegiatan.index')}>
                                Lihat kegiatan <span aria-hidden="true"> →</span>
                            </Link>
                        </article>

                        <article className="portal-dashboard-panel portal-dashboard-location">
                            <div>
                                <p className="portal-dashboard-panel-kicker">LOKASI</p>
                                <h2>Sebaran Lokasi</h2>
                            </div>
                            <div className="portal-dashboard-location-list">
                                <div className="portal-dashboard-location-item">
                                    <div className="portal-dashboard-location-head">
                                        <span>Di Lapangan</span>
                                        <strong>{lapanganRate}%</strong>
                                    </div>
                                    <div className="portal-dashboard-progress-track is-field">
                                        <span style={{ width: `${lapanganRate}%` }} />
                                    </div>
                                    <small>{summary.totalLapangan ?? 0} kegiatan</small>
                                </div>
                                <div className="portal-dashboard-location-item">
                                    <div className="portal-dashboard-location-head">
                                        <span>Di Kampus</span>
                                        <strong>{kampusRate}%</strong>
                                    </div>
                                    <div className="portal-dashboard-progress-track is-campus">
                                        <span style={{ width: `${kampusRate}%` }} />
                                    </div>
                                    <small>{summary.totalKampus ?? 0} kegiatan</small>
                                </div>
                            </div>
                        </article>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
