import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({
    summary = { totalKegiatan: 0, totalDisetujui: 0, totalLapangan: 0, totalKampus: 0, totalJamDisetujui: 0 },
    dppProdi = null,
    analytics = null,
}) {
    const { auth } = usePage().props;
    const isAdmin = auth.user.is_admin;
    const isDpp = auth.user.is_dpp || auth.user.role === 'dpp';
    const isSupervisor = isAdmin || isDpp;
    const approvalRate = summary.totalKegiatan > 0
        ? Math.round((summary.totalDisetujui / summary.totalKegiatan) * 100)
        : 0;
    const locationRate = (value) => (summary.totalKegiatan > 0
        ? Math.round((value / summary.totalKegiatan) * 100)
        : 0);
    const lapanganRate = locationRate(summary.totalLapangan ?? 0);
    const kampusRate = locationRate(summary.totalKampus ?? 0);
    const analyticsReportStatuses = [
        { label: 'Disetujui', key: 'totalLaporanDisetujui', color: 'approved' },
        { label: 'Menunggu', key: 'totalLaporanMenunggu', color: 'waiting' },
        { label: 'Ditolak', key: 'totalLaporanDitolak', color: 'rejected' },
    ];
    const analyticsFacultyMax = Math.max(...(analytics?.mahasiswaPerFakultas ?? []).map((faculty) => faculty.total), 1);
    const percentageOf = (value, total) => (total > 0 ? Math.round((value / total) * 100) : 0);
    const totalJam = Number(summary.totalJamDisetujui ?? 0);
    const totalJamLabel = Number.isInteger(totalJam) ? totalJam : totalJam.toFixed(1);
    const shortcuts = isAdmin
        ? [
            { label: 'Kegiatan', href: route('log-kegiatan.index'), icon: 'report' },
            { label: 'Mahasiswa', href: route('admin.mahasiswa.index'), icon: 'students' },
            { label: 'Jenis Kegiatan', href: route('master-kegiatan.index'), icon: 'schedule' },
        ]
        : isDpp
            ? [
                { label: 'Kegiatan Prodi', href: route('log-kegiatan.index'), icon: 'history' },
                { label: 'Jenis Kegiatan', href: route('master-kegiatan.index'), icon: 'schedule' },
            ]
            : [
                { label: 'Tambah Kegiatan', href: route('log-kegiatan.create'), icon: 'attendance' },
                { label: 'Kegiatan', href: route('log-kegiatan.index'), icon: 'history' },
                { label: 'Jenis Kegiatan', href: route('master-kegiatan.index'), icon: 'schedule' },
            ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="portal-dashboard py-10">
                <div className="portal-dashboard-content mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <header className="portal-dashboard-page-heading">
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal {isAdmin ? 'Admin' : isDpp ? 'Dosen Pembimbing' : 'Mahasiswa'} <span>/</span> Dashboard
                            </p>
                            <h1>Dashboard</h1>
                            <p>{isAdmin
                                ? 'Pantau kegiatan dan perkembangan mahasiswa KKN.'
                                : isDpp
                                    ? `Pantau mahasiswa bimbingan${dppProdi ? ` Program Studi ${dppProdi}` : ''}.`
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
                                    : isDpp
                                        ? `Kelola pemantauan mahasiswa KKN${dppProdi ? ` di ${dppProdi}` : ''}.`
                                        : 'Setiap kegiatan yang Anda catat membantu memantau progres program.'}
                            </span>
                        </div>
                        <aside className="portal-dashboard-identity">
                            <span>{isAdmin ? 'AKUN' : isDpp ? 'PROGRAM STUDI' : 'NRP / NIM'}</span>
                            <strong>{isAdmin ? 'Admin KKN' : isDpp ? (dppProdi || 'Belum diatur') : auth.user.nim || 'Mahasiswa'}</strong>
                            <small>{isAdmin ? 'Administrator' : isDpp ? 'Dosen Pembimbing' : 'Mahasiswa KKN 2026'}</small>
                        </aside>
                    </section>

                    <section className="portal-dashboard-overview">
                        <article className="portal-stat-card portal-stat-card-orange">
                            <span className="portal-stat-icon" aria-hidden="true">▤</span>
                            <p>{isDpp ? 'Mahasiswa Bimbingan' : 'Total Kegiatan'}</p>
                            <strong>{isDpp ? (summary.totalMahasiswa ?? 0) : summary.totalKegiatan}</strong>
                            <small>{isDpp ? `mahasiswa${dppProdi ? ` di ${dppProdi}` : ' dalam prodi Anda'}` : 'kegiatan tercatat'}</small>
                        </article>
                        <article className="portal-stat-card portal-stat-card-success">
                            <span className="portal-stat-icon" aria-hidden="true">✓</span>
                            <p>Disetujui</p>
                            <strong>{summary.totalDisetujui}</strong>
                            <small>kegiatan terverifikasi</small>
                        </article>
                        <article className="portal-stat-card portal-stat-card-info">
                            <span className="portal-stat-icon" aria-hidden="true">◷</span>
                            <p>{!isAdmin && !isDpp ? 'Kegiatan Lapangan' : 'Total Jam Disetujui'}</p>
                            <strong>{!isAdmin && !isDpp ? summary.totalLapangan ?? 0 : totalJamLabel}</strong>
                            <small>{!isAdmin && !isDpp ? 'kegiatan tercatat di lapangan' : 'jam kegiatan yang disetujui'}</small>
                        </article>
                        <article className="portal-dashboard-quick-card">
                            {!isAdmin && !isDpp ? (
                                <>
                                    <div className="portal-dashboard-panel-kicker">AKUMULASI KEGIATAN</div>
                                    <h2>Total Jam Disetujui</h2>
                                    <strong className="mt-3 block text-xl font-bold text-emerald-700">{totalJamLabel} jam</strong>
                                    <p>Jumlah durasi dari seluruh kegiatan yang telah disetujui.</p>
                                </>
                            ) : (
                                <>
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
                                </>
                            )}
                        </article>
                    </section>

                    <section className="portal-dashboard-bottom">
                        {(isAdmin || isDpp) && (
                            <article className="portal-dashboard-panel portal-dashboard-activity">
                                <div>
                                    <p className="portal-dashboard-panel-kicker">ANALISIS PROGRES</p>
                                    <h2>Progres KKN</h2>
                                </div>
                                <p>
                                    Lihat jumlah kegiatan, jam disetujui, dan partisipasi mahasiswa
                                    {dppProdi ? ` di ${dppProdi}` : ' berdasarkan program studi dan kategori kegiatan'}.
                                </p>
                                <Link href={route('progress-analysis.index')}>
                                    Lihat Analisis Progres KKN
                                </Link>
                            </article>
                        )}

                        {!isAdmin && !isDpp && (
                            <article className="portal-dashboard-panel portal-dashboard-activity">
                                <div>
                                    <p className="portal-dashboard-panel-kicker">AKTIVITAS</p>
                                    <h2>Kegiatan</h2>
                                </div>
                                {summary.totalKegiatan > 0 ? (
                                    <p>Terdapat {summary.totalKegiatan} kegiatan tercatat.</p>
                                ) : (
                                    <p>Belum ada kegiatan yang tercatat.</p>
                                )}
                                <Link href={route('log-kegiatan.index')}>
                                    Lihat kegiatan
                                    <span aria-hidden="true"> →</span>
                                </Link>
                            </article>
                        )}

                        {isSupervisor ? (
                            <article className="portal-dashboard-panel portal-dashboard-analytics-panel">
                                <div className="portal-dashboard-analytics-card-heading">
                                    <p className="portal-dashboard-panel-kicker">SEBARAN</p>
                                    <div className="portal-dashboard-analytics-title">
                                        <h2>Mahasiswa per Fakultas</h2>
                                        <strong>{analytics?.totalMahasiswa ?? 0}</strong>
                                    </div>
                                </div>
                                {!analytics || analytics.totalMahasiswa === 0 || analytics.mahasiswaPerFakultas.length === 0 ? (
                                    <div className="portal-dashboard-empty-state">
                                        <span className="portal-dashboard-empty-icon" aria-hidden="true">♙</span>
                                        <div>
                                            <strong>Belum ada data sebaran</strong>
                                            <p>Sebaran mahasiswa akan tampil setelah mahasiswa terdaftar.</p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="portal-dashboard-analytics-rows">
                                        {analytics.mahasiswaPerFakultas.map((faculty) => {
                                            const percentage = percentageOf(faculty.total, analytics.totalMahasiswa);

                                            return (
                                                <div className="portal-dashboard-analytics-row" key={faculty.fakultas}>
                                                    <span>{faculty.fakultas} ({faculty.total})</span>
                                                    <div className="portal-dashboard-progress-track">
                                                        <span style={{ width: `${percentageOf(faculty.total, analyticsFacultyMax)}%` }} />
                                                    </div>
                                                    <strong>{percentage}%</strong>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </article>
                        ) : (
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
                        )}

                        {isSupervisor ? (
                            <article className="portal-dashboard-panel portal-dashboard-analytics-panel">
                                <div className="portal-dashboard-analytics-card-heading">
                                    <p className="portal-dashboard-panel-kicker">VERIFIKASI</p>
                                    <div className="portal-dashboard-analytics-title">
                                        <h2>Status Laporan</h2>
                                        <strong>{analytics?.totalLaporan ?? 0}</strong>
                                    </div>
                                </div>
                                {!analytics || analytics.totalLaporan === 0 ? (
                                    <div className="portal-dashboard-empty-state">
                                        <span className="portal-dashboard-empty-icon" aria-hidden="true">✓</span>
                                        <div>
                                            <strong>Belum ada laporan</strong>
                                            <p>Status verifikasi akan tampil setelah laporan kegiatan dikirim.</p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="portal-dashboard-analytics-rows">
                                        {analyticsReportStatuses.map((status) => {
                                            const total = analytics[status.key];
                                            const percentage = percentageOf(total, analytics.totalLaporan);

                                            return (
                                                <div className="portal-dashboard-analytics-row" key={status.key}>
                                                    <span>{status.label} ({total})</span>
                                                    <div className={`portal-dashboard-progress-track is-${status.color}`}>
                                                        <span style={{ width: `${percentage}%` }} />
                                                    </div>
                                                    <strong>{percentage}%</strong>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </article>
                        ) : (
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
                        )}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
