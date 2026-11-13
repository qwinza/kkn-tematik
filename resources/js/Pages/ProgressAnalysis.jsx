import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

const formatHours = (hours) => {
    const total = Number(hours ?? 0);

    return Number.isInteger(total) ? total : total.toFixed(1);
};

export default function ProgressAnalysis({ analysis, isDpp, dppProdi, prodis = [], selectedKodeProdi, selectedNamaProdi }) {
    const summary = analysis.summary;
    const prodiRows = analysis.per_prodi || [];
    const activityRows = analysis.per_kegiatan || [];
    const totalApprovedActivities = Number(summary.total_kegiatan ?? 0);

    return (
        <AuthenticatedLayout>
            <Head title="Analisis Progres KKN" />

            <div className="portal-analytics-page">
                <div className="portal-analytics-content">
                    <header className="portal-dashboard-page-heading portal-analytics-heading">
                        <div>
                            <p className="portal-dashboard-breadcrumb">
                                Portal {isDpp ? 'Dosen Pembimbing' : 'Admin'} <span>/</span> Analisis Progres KKN
                            </p>
                            <h1>Analisis Progres KKN</h1>
                            <p>Ringkasan kegiatan disetujui, akumulasi jam, dan partisipasi mahasiswa.</p>
                        </div>
                        {isDpp ? (
                            <span className="portal-analytics-semester">{dppProdi}</span>
                        ) : (
                            <select
                                aria-label="Filter analisis berdasarkan program studi"
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={selectedKodeProdi || ''}
                                onChange={(event) => router.get(route('progress-analysis.index'), {
                                    kode_prodi: event.target.value || undefined,
                                }, { preserveScroll: true, preserveState: true, replace: true })}
                            >
                                <option value="">Semua Program Studi</option>
                                {prodis.map((prodi) => (
                                    <option key={prodi.kode_prodi} value={prodi.kode_prodi}>{prodi.nama_prodi}</option>
                                ))}
                            </select>
                        )}
                    </header>

                    <section className="portal-analytics-summary" aria-label="Ringkasan progres KKN">
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon students" aria-hidden="true">M</span>
                            <p>Mahasiswa</p>
                            <strong>{summary.total_mahasiswa}</strong>
                            <small>terdaftar</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon reports" aria-hidden="true">✓</span>
                            <p>Mahasiswa Aktif</p>
                            <strong>{summary.mahasiswa_aktif}</strong>
                            <small>{summary.persentase_aktif}% memiliki kegiatan disetujui</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon approved" aria-hidden="true">K</span>
                            <p>Kegiatan Disetujui</p>
                            <strong>{summary.total_kegiatan}</strong>
                            <small>laporan</small>
                        </article>
                        <article className="portal-analytics-card">
                            <span className="portal-analytics-icon waiting" aria-hidden="true">J</span>
                            <p>Total Jam</p>
                            <strong>{formatHours(summary.total_jam)}</strong>
                            <small>jam disetujui</small>
                        </article>
                    </section>

        <section className="portal-analytics-charts" aria-label="Analisis program studi dan nama kegiatan">
                        <article className="portal-analytics-panel">
                            <p className="portal-analytics-kicker">PROGRES MAHASISWA</p>
                            <h2>{isDpp ? 'Progres Prodi Bimbingan' : 'Progres per Program Studi'}</h2>
                            {prodiRows.length === 0 ? (
                                <p className="portal-analytics-empty">Belum ada data mahasiswa.</p>
                            ) : (
                                <div className="portal-analytics-rows">
                                    {prodiRows.map((prodi) => (
                                        <div className="portal-analytics-row portal-progress-row" key={prodi.kode_prodi}>
                                            <div className="min-w-0">
                                                <span className="portal-analytics-row-label portal-progress-row-label">
                                                    {prodi.prodi} ({prodi.mahasiswa_aktif}/{prodi.total_mahasiswa} aktif)
                                                </span>
                                                <small className="portal-progress-row-meta">
                                                    {prodi.total_kegiatan} kegiatan · {formatHours(prodi.total_jam)} jam disetujui
                                                </small>
                                            </div>
                                            <span className="portal-analytics-track">
                                                <span style={{ width: `${prodi.persentase_aktif}%` }} />
                                            </span>
                                            <strong>{prodi.persentase_aktif}%</strong>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </article>

                        <article className="portal-analytics-panel">
                            <p className="portal-analytics-kicker">KELOMPOK KEGIATAN</p>
                            <h2>{selectedNamaProdi ? `Progres Kegiatan ${selectedNamaProdi}` : 'Progres per Nama Kegiatan'}</h2>
                            {activityRows.length === 0 ? (
                                <p className="portal-analytics-empty">Belum ada kegiatan yang disetujui.</p>
                            ) : (
                                <div className="portal-analytics-rows">
                                    {activityRows.map((activity) => (
                                        <div className="portal-analytics-row portal-progress-row" key={activity.nama_kegiatan}>
                                            <div className="min-w-0">
                                                <span className="portal-analytics-row-label portal-progress-row-label">
                                                    {activity.nama_kegiatan} ({activity.total_kegiatan})
                                                </span>
                                                <small className="portal-progress-row-meta">
                                                    {formatHours(activity.total_jam)} jam disetujui
                                                </small>
                                            </div>
                                            <span className="portal-analytics-track approved">
                                                <span style={{ width: `${(activity.total_kegiatan / Math.max(totalApprovedActivities, 1)) * 100}%` }} />
                                            </span>
                                            <strong>{Math.round((activity.total_kegiatan / Math.max(totalApprovedActivities, 1)) * 100)}%</strong>
                                            <strong>{activity.total_kegiatan}</strong>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </article>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
