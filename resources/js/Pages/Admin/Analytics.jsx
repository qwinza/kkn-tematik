import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

function SummaryIcon({ type }) {
    if (type === 'students') {
        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="9" cy="8" r="3" />
                <path d="M3.5 19v-1.5A4.5 4.5 0 0 1 8 13h2a4.5 4.5 0 0 1 4.5 4.5V19M16 5.5a3 3 0 0 1 0 5.8M17 13h1a3.5 3.5 0 0 1 3.5 3.5V18" />
            </svg>
        );
    }

    if (type === 'approved') {
        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m5 12 4.5 4.5L19 7" />
            </svg>
        );
    }

    if (type === 'waiting') {
        return (
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="8.5" />
                <path d="M12 7v5l3 2" />
            </svg>
        );
    }

    return (
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 4.5h9l4 4V20H6zM14.5 4.5V9H19M9 13h7M9 16.5h7" />
        </svg>
    );
}

export default function Analytics({ summary }) {
    const reportStatuses = [
        { label: 'Disetujui', key: 'totalDisetujui', color: 'approved' },
        { label: 'Menunggu', key: 'totalMenunggu', color: 'waiting' },
        { label: 'Ditolak', key: 'totalDitolak', color: 'rejected' },
    ];
    const facultyMax = Math.max(...summary.mahasiswaPerFakultas.map((faculty) => faculty.total), 1);

    return (
        <AuthenticatedLayout>
            <Head title="Analitik" />

            <div className="portal-analytics-page">
                <div className="portal-analytics-content">
                    <header className="portal-dashboard-page-heading portal-analytics-heading">
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal Admin <span>/</span> Analitik
                            </p>
                            <h1>Analitik</h1>
                            <p>Partisipasi mahasiswa dan status verifikasi laporan.</p>
                        </div>
                        <span className="portal-analytics-semester">Semester 2026</span>
                    </header>
                    <section className="portal-analytics-summary" aria-label="Ringkasan analitik">
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon students"><SummaryIcon type="students" /></span>
                            <p>Mahasiswa</p>
                            <strong>{summary.totalMahasiswa}</strong>
                            <small>terdaftar</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon reports"><SummaryIcon type="reports" /></span>
                            <p>Laporan</p>
                            <strong>{summary.totalLaporan}</strong>
                            <small>terkirim</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon approved"><SummaryIcon type="approved" /></span>
                            <p>Disetujui</p>
                            <strong>{summary.totalDisetujui}</strong>
                            <small>terverifikasi</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon waiting"><SummaryIcon type="waiting" /></span>
                            <p>Menunggu</p>
                            <strong>{summary.totalMenunggu}</strong>
                            <small>perlu ditinjau</small>
                        </article>
                    </section>

                    <section className="portal-analytics-charts" aria-label="Rincian analitik">
                        <article className="portal-analytics-panel">
                            <p className="portal-analytics-kicker">SEBARAN</p>
                            <h2>Mahasiswa per Fakultas</h2>
                            {summary.mahasiswaPerFakultas.length === 0 ? (
                                <p className="portal-analytics-empty">Belum ada data mahasiswa.</p>
                            ) : (
                                <div className="portal-analytics-rows">
                                    {summary.mahasiswaPerFakultas.map((faculty) => (
                                        <div className="portal-analytics-row" key={faculty.fakultas}>
                                            <span className="portal-analytics-row-label">{faculty.fakultas} ({faculty.total})</span>
                                            <span className="portal-analytics-track"><span style={{ width: `${(faculty.total / facultyMax) * 100}%` }} /></span>
                                            <strong>{Math.round((faculty.total / Math.max(summary.totalMahasiswa, 1)) * 100)}%</strong>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </article>

                        <article className="portal-analytics-panel">
                            <p className="portal-analytics-kicker">VERIFIKASI</p>
                            <h2>Status Laporan</h2>
                            {summary.totalLaporan === 0 ? (
                                <p className="portal-analytics-empty">Belum ada laporan terkirim.</p>
                            ) : (
                                <div className="portal-analytics-rows">
                                    {reportStatuses.map((status) => {
                                        const total = summary[status.key];
                                        const percentage = Math.round((total / summary.totalLaporan) * 100);

                                        return (
                                            <div className="portal-analytics-row" key={status.key}>
                                                <span className="portal-analytics-row-label">{status.label} ({total})</span>
                                                <span className={`portal-analytics-track ${status.color}`}><span style={{ width: `${percentage}%` }} /></span>
                                                <strong>{percentage}%</strong>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </article>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
