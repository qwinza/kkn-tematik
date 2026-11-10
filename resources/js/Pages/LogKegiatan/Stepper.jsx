import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function LogKegiatanStepper({ log, currentStep = 1, student, masterKegiatans = [], prodis = [], rencanaKehadirans = [] }) {
    const [activeStep, setActiveStep] = useState(currentStep || 1);
    const [currentLogId, setCurrentLogId] = useState(log?.id || null);
    const [currentLogRouteKey, setCurrentLogRouteKey] = useState(log?.route_key || null);
    const [stepValidationError, setStepValidationError] = useState(null);
    const [isSaving, setIsSaving] = useState(false);

    const [geoLoading, setGeoLoading] = useState(false);
    const [geoMessage, setGeoMessage] = useState(null);

    // Main Stepper Form state
    const form = useForm({
        log_id: currentLogId,
        step: activeStep,
        is_draft_only: false,
        action: 'continue',

        // Step 1: Identity
        email: student?.email || log?.peserta?.user?.email || '',
        nama_lengkap: student?.nama_lengkap || log?.peserta?.nama_lengkap || '',
        nim: student?.nim || log?.peserta?.nim || '',
        kode_prodi: student?.kode_prodi || log?.peserta?.kode_prodi || '',

        // Step 2: Schedule
        rencana_kehadiran_id: log?.rencana_kehadiran_id || '',

        // Step 3: Activity
        master_kegiatan_id: log?.master_kegiatan_id || '',

        // Step 4: Details
        lokasi_kegiatan: log?.lokasi_kegiatan || 'Lapangan',
        tipe_lokasi: log?.latitude ? 'manual' : 'otomatis',
        latitude: log?.latitude || '',
        longitude: log?.longitude || '',
        deskripsi_kegiatan: log?.deskripsi_kegiatan || '',

        // Step 5: Evidence
        foto_kegiatan: [],

        // Step 6: Outputs
        deskripsi_luaran: log?.deskripsi_luaran || '',
        file_luaran: [],
    });
    const photoErrors = Object.entries(form.errors).filter(([field]) => field === 'foto_kegiatan' || field.startsWith('foto_kegiatan.'));
    const outputFileErrors = Object.entries(form.errors).filter(([field]) => field === 'file_luaran' || field.startsWith('file_luaran.'));

    // Synchronize activeStep and currentLogId when props change
    useEffect(() => {
        if (currentStep) {
            setActiveStep(currentStep);
        }
    }, [currentStep]);

    useEffect(() => {
        if (log?.id) {
            setCurrentLogId(log.id);
            setCurrentLogRouteKey(log.route_key);
            form.setData('log_id', log.id);
        }
    }, [log?.id, log?.route_key]);

    const filteredActivities = masterKegiatans;

    // Select first activity when faculty changes if none selected or mismatched
    useEffect(() => {
        if (filteredActivities.length > 0) {
            const hasMatch = filteredActivities.some((k) => k.id === form.data.master_kegiatan_id);
            if (!hasMatch && !log?.master_kegiatan_id) {
                form.setData('master_kegiatan_id', filteredActivities[0].id);
            }
        }
    }, [filteredActivities]);

    // Geolocation API (Browser GPS)
    const handleGetCurrentLocation = () => {
        if (!navigator.geolocation) {
            setGeoMessage({ type: 'error', text: 'Fitur lokasi tidak didukung oleh peramban Anda.' });
            return;
        }

        setGeoLoading(true);
        setGeoMessage(null);

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude.toFixed(8);
                const lng = position.coords.longitude.toFixed(8);
                form.setData((prev) => ({
                    ...prev,
                    latitude: lat,
                    longitude: lng,
                }));
                setGeoLoading(false);
                setGeoMessage({
                    type: 'success',
                    text: `Berhasil mendapatkan koordinat: ${lat}, ${lng}`,
                });
            },
            () => {
                setGeoLoading(false);
                setGeoMessage({
                    type: 'error',
                    text: 'Lokasi tidak dapat dideteksi. Periksa izin lokasi atau isi koordinat secara manual.',
                });
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    };

    // Save Handlers
    const saveCurrentStep = (targetStep, isDraft = false, actionType = 'continue') => {
        const targetLogId = currentLogId || log?.id;
        const targetLogRouteKey = currentLogRouteKey || log?.route_key;
        const url = targetLogRouteKey
            ? route('log-kegiatan.save-step', targetLogRouteKey)
            : route('log-kegiatan.save-step.create');

        form.transform((data) => ({
            ...data,
            log_id: targetLogId,
            step: targetStep,
            is_draft_only: isDraft,
            action: actionType,
        }));

        form.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                setStepValidationError(null);
                const newLog = page.props?.log;
                if (newLog?.id) {
                    setCurrentLogId(newLog.id);
                    setCurrentLogRouteKey(newLog.route_key);
                }
            },
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                if (firstError) {
                    setStepValidationError(firstError);
                }
            },
        });
    };

    const handleContinue = (e) => {
        e?.preventDefault();
        setStepValidationError(null);

        // Validation per step before continuing
        if (activeStep === 1) {
            if (!form.data.nama_lengkap) {
                setStepValidationError('Nama lengkap wajib diisi.');
                return;
            }
            if (!form.data.kode_prodi) {
                setStepValidationError('Pilih program studi terlebih dahulu.');
                return;
            }
        } else if (activeStep === 2) {
            if (!form.data.rencana_kehadiran_id) {
                setStepValidationError('Pilih jadwal kehadiran yang sudah diatur.');
                return;
            }
        } else if (activeStep === 3) {
            if (!form.data.master_kegiatan_id) {
                setStepValidationError('Pilih salah satu kode aktivitas kegiatan.');
                return;
            }
        } else if (activeStep === 4) {
            if (!form.data.deskripsi_kegiatan) {
                setStepValidationError('Deskripsi rincian kegiatan wajib diisi.');
                return;
            }
        } else if (activeStep === 5) {
            const hasExistingPhotos = log?.files?.some((file) => file.jenis_file === 'foto_kegiatan');
            if (!form.data.foto_kegiatan.length && !hasExistingPhotos) {
                setStepValidationError('Foto dokumentasi kegiatan wajib dipilih.');
                return;
            }
        } else if (activeStep === 6) {
            if (!form.data.deskripsi_luaran) {
                setStepValidationError('Deskripsi luaran wajib diisi.');
                return;
            }
            const hasExistingOutputs = log?.files?.some((file) => file.jenis_file === 'file_luaran');
            if (!form.data.file_luaran.length && !hasExistingOutputs) {
                setStepValidationError('Berkas luaran kegiatan wajib diunggah.');
                return;
            }
        }

        // Save this step to backend
        const currentStepBeingSaved = activeStep;
        saveCurrentStep(currentStepBeingSaved, false, 'continue');

        // Advance to next step in UI immediately
        if (activeStep < 7) {
            setActiveStep(activeStep + 1);
        }
    };

    const handleSaveDraft = (e) => {
        e?.preventDefault();
        setStepValidationError(null);
        saveCurrentStep(activeStep, true, 'draft');
    };

    const handleFinalSubmit = (e) => {
        e?.preventDefault();
        setStepValidationError(null);
        saveCurrentStep(7, false, 'submit');
    };

    const steps = [
        { number: 1, label: 'Identitas' },
        { number: 2, label: 'Jadwal' },
        { number: 3, label: 'Kegiatan' },
        { number: 4, label: 'Rincian' },
        { number: 5, label: 'Dokumentasi' },
        { number: 6, label: 'Luaran' },
        { number: 7, label: 'Tinjau' },
    ];

    const currentActivityObj = masterKegiatans.find((k) => k.id === Number(form.data.master_kegiatan_id)) || log?.master_kegiatan;
    const selectedProdi = prodis.find((prodi) => prodi.kode_prodi === form.data.kode_prodi);
    const selectedRencanaKehadiran = rencanaKehadirans.find(
        (rencana) => Number(rencana.id) === Number(form.data.rencana_kehadiran_id)
    );
    const pageTitle = log ? 'Ubah Kegiatan' : 'Tambah Kegiatan';

    return (
        <AuthenticatedLayout showSweetAlerts={false}>
            <Head title={pageTitle} />

            <div className="portal-stepper-page min-h-screen bg-[#fbfcfb] py-10 px-4 sm:px-6 lg:px-8">
                <div className="portal-stepper-content mx-auto max-w-4xl">
                    <div className="portal-stepper-heading text-center mb-8">
                        <h1 className="text-3xl font-extrabold text-[#111827] tracking-tight">
                            {pageTitle}
                        </h1>
                        <p className="mt-2 text-sm text-[#4b5563]">
                            Isi formulir ini untuk mencatat kegiatan KKN yang telah dilaksanakan.
                        </p>
                    </div>

                    {/* Stepper Progress Bar */}
                    <div className="portal-stepper-progress mb-10">
                        <div className="relative flex items-center justify-between">
                            {/* Horizontal Line behind */}
                            <div className="absolute left-6 right-6 top-4 -translate-y-1/2 h-0.5 bg-gray-200 z-0" />

                            {steps.map((step) => {
                                const isCurrent = activeStep === step.number;
                                const isCompleted = activeStep > step.number || ['menunggu', 'disetujui', 'ditolak'].includes(log?.status);

                                return (
                                    <div key={step.number} className="relative z-10 flex flex-col items-center">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setStepValidationError(null);
                                                setActiveStep(step.number);
                                            }}
                                            className={`flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition-all shadow-xs cursor-pointer ${
                                                isCurrent
                                                    ? 'bg-[#064e3b] text-white ring-4 ring-emerald-100 ring-offset-1'
                                                    : isCompleted
                                                    ? 'bg-[#ecfdf5] border-2 border-[#059669] text-[#059669]'
                                                    : 'bg-white border-2 border-gray-200 text-gray-500 hover:border-emerald-500'
                                            }`}
                                        >
                                            {isCompleted && !isCurrent ? (
                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                            ) : (
                                                step.number
                                            )}
                                        </button>
                                        <span
                                            className={`mt-2 text-xs font-medium text-center ${
                                                isCurrent
                                                    ? 'text-[#064e3b] font-bold'
                                                    : isCompleted
                                                    ? 'text-gray-700'
                                                    : 'text-gray-400'
                                            }`}
                                        >
                                            {step.label}
                                        </span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Validation Error Alert Banner */}
                    {(stepValidationError || Object.keys(form.errors).length > 0) && (
                        <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs text-red-800 flex items-start gap-3">
                            <span className="font-bold text-red-600 text-sm">⚠</span>
                            <div>
                                <span className="font-bold block">Mohon lengkapi bagian ini terlebih dahulu:</span>
                                <span>{stepValidationError || Object.values(form.errors)[0]}</span>
                            </div>
                        </div>
                    )}

                    {/* Stepper Card Content */}
                    <div className="portal-stepper-card rounded-2xl border border-gray-100 bg-white p-6 sm:p-10 shadow-sm">
                        {/* STEP 1: IDENTITY */}
                        {activeStep === 1 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 1 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Identitas
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Pastikan data mahasiswa yang mengisi kegiatan sudah benar.
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="email" value="Alamat email" className="text-xs font-semibold text-gray-700" />
                                        <TextInput
                                            id="email"
                                            type="email"
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 text-sm"
                                            value={form.data.email}
                                            placeholder="nadia.amalia@itenas.ac.id"
                                            autoComplete="off"
                                            disabled
                                        />
                                        <InputError message={form.errors.email} className="mt-1" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="nama_lengkap" value="Nama lengkap" className="text-xs font-semibold text-gray-700" />
                                        <TextInput
                                            id="nama_lengkap"
                                            type="text"
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 text-sm"
                                            value={form.data.nama_lengkap}
                                            placeholder="Nadia Amalia"
                                            autoComplete="off"
                                            disabled
                                        />
                                        <InputError message={form.errors.nama_lengkap} className="mt-1" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="nim" value="NIM" className="text-xs font-semibold text-gray-700" />
                                        <TextInput
                                            id="nim"
                                            type="text"
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600 bg-gray-50"
                                            value={form.data.nim}
                                            readOnly
                                        />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="kode_prodi" value="Program Studi" className="text-xs font-semibold text-gray-700"/>
                                        <select
                                            id="kode_prodi"
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                                            value={form.data.kode_prodi}
                                            onChange={(e) => form.setData('kode_prodi', e.target.value)}
                                            disabled={Boolean(student?.kode_prodi || log?.peserta?.kode_prodi)}
                                            required
                                        >
                                            <option value="">Pilih program studi</option>
                                            {prodis.map((prodi) => (
                                                <option key={prodi.kode_prodi} value={prodi.kode_prodi}>
                                                    {prodi.kode_prodi} — {prodi.nama_prodi}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={form.errors.kode_prodi} className="mt-1" />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* STEP 2: SCHEDULE */}
                        {activeStep === 2 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 2 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Jadwal
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Isi tanggal dan waktu pelaksanaan kegiatan.
                                    </p>
                                </div>

                                <div className="space-y-5">
                                    <div>
                                        <InputLabel htmlFor="rencana_kehadiran_id" value="Jadwal kehadiran" className="text-xs font-semibold text-gray-700" />
                                        <select
                                            id="rencana_kehadiran_id"
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                                            value={form.data.rencana_kehadiran_id}
                                            onChange={(event) => form.setData('rencana_kehadiran_id', event.target.value)}
                                            required
                                        >
                                            <option value="">Pilih jadwal yang sudah diatur</option>
                                            {rencanaKehadirans.map((rencana) => (
                                                <option key={rencana.id} value={rencana.id}>
                                                    {rencana.tanggal_rencana.slice(0, 10)} - {rencana.jam_kehadiran}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={form.errors.rencana_kehadiran_id} className="mt-1" />
                                    </div>

                                    {selectedRencanaKehadiran ? (
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                                <span className="block text-xs font-semibold uppercase text-gray-500">Tanggal</span>
                                                <span className="mt-1 block text-sm font-medium text-gray-900">
                                                    {selectedRencanaKehadiran.tanggal_rencana.slice(0, 10)}
                                                </span>
                                            </div>
                                            <div className="rounded-lg border border-gray-100 bg-gray-50 p-4">
                                                <span className="block text-xs font-semibold uppercase text-gray-500">Sesi</span>
                                                <span className="mt-1 block text-sm font-medium text-gray-900">
                                                    {selectedRencanaKehadiran.jam_kehadiran}
                                                </span>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-gray-600">
                                            Belum ada jadwal kehadiran.{' '}
                                            <Link href={route('mahasiswa.schedule.index')} className="font-semibold text-emerald-700 underline">
                                                Atur jadwal kehadiran
                                            </Link>
                                            {' '}terlebih dahulu.
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* STEP 3: ACTIVITY */}
                        {activeStep === 3 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 3 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Kegiatan
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Pilih kegiatan yang sesuai dengan program studi Anda.
                                    </p>
                                </div>

                                <div>
                                    <InputLabel htmlFor="master_kegiatan_id" value="Kegiatan / kode kegiatan" className="text-xs font-semibold text-gray-700" />
                                    <select
                                        id="master_kegiatan_id"
                                        className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                                        value={form.data.master_kegiatan_id}
                                        onChange={(e) => form.setData('master_kegiatan_id', e.target.value)}
                                        required
                                        disabled={filteredActivities.length === 0}
                                    >
                                        <option value="">-- Pilih Kode Kegiatan --</option>
                                        {filteredActivities.map((k) => (
                                            <option key={k.id} value={k.id}>
                                                {k.kode_kegiatan} — {k.nama_kegiatan}
                                            </option>
                                        ))}
                                    </select>
                                    {filteredActivities.length === 0 && (
                                        <p className="mt-2 text-sm text-amber-700">Belum ada kegiatan untuk program studi ini.</p>
                                    )}
                                    <InputError message={form.errors.master_kegiatan_id} className="mt-1" />
                                </div>
                            </div>
                        )}

                        {/* STEP 4: DETAILS */}
                        {activeStep === 4 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 4 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Rincian Kegiatan
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Jelaskan kegiatan yang dilakukan beserta lokasi pelaksanaannya.
                                    </p>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <InputLabel value="Lokasi Kegiatan *" className="text-xs font-semibold text-gray-700" />
                                        <div className="mt-2 flex gap-6">
                                            {['Lapangan', 'Kampus ITENAS'].map((loc) => (
                                                <label key={loc} className="inline-flex items-center text-sm">
                                                    <input
                                                        type="radio"
                                                        name="lokasi_kegiatan"
                                                        value={loc}
                                                        checked={form.data.lokasi_kegiatan === loc}
                                                        onChange={(e) => form.setData('lokasi_kegiatan', e.target.value)}
                                                        className="text-emerald-600 focus:ring-emerald-500"
                                                    />
                                                    <span className="ms-2 text-gray-800 font-medium">{loc}</span>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError message={form.errors.lokasi_kegiatan} className="mt-1" />
                                    </div>

                                    {/* GPS Coordinate options */}
                                    <div className="rounded-2xl border border-gray-100 bg-[#f9fafb] p-5">
                                        <div className="text-xs font-semibold text-gray-700">Metode Koordinat Lokasi (GPS)</div>
                                        <div className="mt-2 flex gap-4 text-xs">
                                            <label className="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="tipe_lokasi"
                                                    value="otomatis"
                                                    checked={form.data.tipe_lokasi === 'otomatis'}
                                                    onChange={(e) => form.setData('tipe_lokasi', e.target.value)}
                                                    className="text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="ms-1.5 font-medium text-gray-700">1. Lokasi saat ini (GPS perangkat)</span>
                                            </label>
                                            <label className="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    name="tipe_lokasi"
                                                    value="manual"
                                                    checked={form.data.tipe_lokasi === 'manual'}
                                                    onChange={(e) => form.setData('tipe_lokasi', e.target.value)}
                                                    className="text-emerald-600 focus:ring-emerald-500"
                                                />
                                                <span className="ms-1.5 font-medium text-gray-700">2. Input Manual</span>
                                            </label>
                                        </div>

                                        {form.data.tipe_lokasi === 'otomatis' && (
                                            <div className="mt-3">
                                                <button
                                                    type="button"
                                                    onClick={handleGetCurrentLocation}
                                                    disabled={geoLoading}
                                                    className="rounded-lg bg-white border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                >
                                                    {geoLoading ? 'Mendeteksi lokasi...' : '📍 Gunakan lokasi saat ini'}
                                                </button>
                                                {geoMessage && (
                                                    <p className={`mt-2 text-xs ${geoMessage.type === 'success' ? 'text-emerald-600' : 'text-red-600'}`}>
                                                        {geoMessage.text}
                                                    </p>
                                                )}
                                            </div>
                                        )}

                                        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <InputLabel htmlFor="latitude" value="Garis lintang" className="text-xs text-gray-600" />
                                                <TextInput
                                                    id="latitude"
                                                    type="number"
                                                    step="any"
                                                    className="mt-1 block w-full rounded-xl border-gray-200 text-xs"
                                                    placeholder="-6.8975000"
                                                    value={form.data.latitude}
                                                    onChange={(e) => form.setData('latitude', e.target.value)}
                                                />
                                            </div>
                                            <div>
                                                <InputLabel htmlFor="longitude" value="Garis bujur" className="text-xs text-gray-600" />
                                                <TextInput
                                                    id="longitude"
                                                    type="number"
                                                    step="any"
                                                    className="mt-1 block w-full rounded-xl border-gray-200 text-xs"
                                                    placeholder="107.6358000"
                                                    value={form.data.longitude}
                                                    onChange={(e) => form.setData('longitude', e.target.value)}
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="deskripsi_kegiatan" value="Rincian Kegiatan *" className="text-xs font-semibold text-gray-700" />
                                        <textarea
                                            id="deskripsi_kegiatan"
                                            rows={5}
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                                            placeholder="Deskripsikan pekerjaan lapangan dan aktivitas yang dilakukan hari ini secara rinci..."
                                            value={form.data.deskripsi_kegiatan}
                                            onChange={(e) => form.setData('deskripsi_kegiatan', e.target.value)}
                                            required
                                        />
                                        <InputError message={form.errors.deskripsi_kegiatan} className="mt-1" />
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* STEP 5: EVIDENCE */}
                        {activeStep === 5 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 5 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Dokumentasi
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Unggah foto sebagai bukti kegiatan.
                                    </p>
                                </div>

                                <div>
                                    <InputLabel htmlFor="foto_kegiatan" value="Unggah foto kegiatan (maks. 50 MB per berkas, format JPG/PNG/WEBP) *" className="text-xs font-semibold text-gray-700" />
                                    <input
                                        id="foto_kegiatan"
                                        type="file"
                                        multiple
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        className="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                        onChange={(e) => form.setData('foto_kegiatan', Array.from(e.target.files || []))}
                                    />
                                    {photoErrors.map(([field, message]) => (
                                        <InputError key={field} message={message} className="mt-1" />
                                    ))}
                                    {form.data.foto_kegiatan.length > 0 && (
                                        <p className="mt-2 text-xs text-gray-500">
                                            {form.data.foto_kegiatan.length} file dipilih: {form.data.foto_kegiatan.map((file) => file.name).join(', ')}
                                        </p>
                                    )}

                                    {log?.files?.some((file) => file.jenis_file === 'foto_kegiatan') && (
                                        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                            {log.files.filter((file) => file.jenis_file === 'foto_kegiatan').map((file) => (
                                                <div key={file.id}>
                                                    <img src={file.url} alt={file.nama_file_asli} className="h-36 w-full rounded-lg border object-cover" />
                                                    <span className="mt-1 block truncate text-xs text-gray-500">{file.nama_file_asli}</span>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* STEP 6: OUTPUTS */}
                        {activeStep === 6 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 6 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Luaran Kegiatan
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Jelaskan hasil atau luaran yang dihasilkan dari kegiatan.
                                    </p>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <InputLabel htmlFor="deskripsi_luaran" value="Deskripsi luaran *" className="text-xs font-semibold text-gray-700" />
                                        <textarea
                                            id="deskripsi_luaran"
                                            rows={4}
                                            className="mt-1.5 block w-full rounded-xl border-gray-200 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                                            placeholder="Tuliskan luaran atau hasil konkret kegiatan (misal: draf peta sampah, data kuesioner, catatan singkat)..."
                                            value={form.data.deskripsi_luaran}
                                            onChange={(e) => form.setData('deskripsi_luaran', e.target.value)}
                                            required
                                        />
                                        <InputError message={form.errors.deskripsi_luaran} className="mt-1" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="file_luaran" value="Unggah berkas luaran (maks. 50 MB per berkas, PDF/DOCX/ZIP/gambar) *" className="text-xs font-semibold text-gray-700" />
                                        <input
                                            id="file_luaran"
                                            type="file"
                                            multiple
                                            accept=".pdf,.doc,.docx,.zip,.rar,image/*"
                                            className="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                            onChange={(e) => form.setData('file_luaran', Array.from(e.target.files || []))}
                                        />
                                        {outputFileErrors.map(([field, message]) => (
                                            <InputError key={field} message={message} className="mt-1" />
                                        ))}
                                        {form.data.file_luaran.length > 0 && (
                                            <p className="mt-2 text-xs text-gray-500">
                                                {form.data.file_luaran.length} file dipilih: {form.data.file_luaran.map((file) => file.name).join(', ')}
                                            </p>
                                        )}

                                        {log?.files?.some((file) => file.jenis_file === 'file_luaran') && (
                                            <div className="mt-3 flex flex-col gap-2 text-xs">
                                                <span className="text-gray-500">Berkas luaran yang tersimpan:</span>
                                                {log.files.filter((file) => file.jenis_file === 'file_luaran').map((file) => (
                                                    <a key={file.id} href={file.url} target="_blank" rel="noreferrer" className="text-emerald-700 font-semibold underline">
                                                        {file.nama_file_asli}
                                                    </a>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* STEP 7: REVIEW */}
                        {activeStep === 7 && (
                            <div>
                                <div className="mb-6">
                                    <span className="text-xs font-bold tracking-widest text-[#059669] uppercase">
                                        TAHAP 7 DARI 7
                                    </span>
                                    <h2 className="mt-1 text-2xl font-bold text-gray-900">
                                        Tinjau Kegiatan
                                    </h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Periksa kembali seluruh informasi sebelum mengirim kegiatan.
                                    </p>
                                </div>

                                <div className="space-y-4 text-sm">
                                    <div className="rounded-xl border border-gray-100 bg-[#f9fafb] p-4">
                                        <div className="font-bold text-gray-800 mb-2">1. Identitas</div>
                                        <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                            <div><strong>Nama:</strong> {form.data.nama_lengkap}</div>
                                            <div><strong>NIM:</strong> {form.data.nim}</div>
                                            <div><strong>Alamat email:</strong> {form.data.email}</div>
                                            <div><strong>Program studi:</strong> {selectedProdi?.nama_prodi ?? '-'}</div>
                                        </div>
                                    </div>

                                    <div className="rounded-xl border border-gray-100 bg-[#f9fafb] p-4">
                                        <div className="font-bold text-gray-800 mb-2">2. Jadwal</div>
                                        <div className="grid grid-cols-2 gap-2 text-xs text-gray-600">
                                            <div><strong>Tanggal:</strong> {selectedRencanaKehadiran?.tanggal_rencana.slice(0, 10) ?? '-'}</div>
                                            <div><strong>Sesi:</strong> {selectedRencanaKehadiran?.jam_kehadiran ?? '-'}</div>
                                        </div>
                                    </div>

                                    <div className="rounded-xl border border-gray-100 bg-[#f9fafb] p-4">
                                        <div className="font-bold text-gray-800 mb-2">3. Kegiatan</div>
                                        <div className="text-xs text-gray-600">
                                            <strong>Nama kegiatan:</strong> {currentActivityObj?.kode_kegiatan} — {currentActivityObj?.nama_kegiatan} ({currentActivityObj?.kategori})
                                        </div>
                                    </div>

                                    <div className="rounded-xl border border-gray-100 bg-[#f9fafb] p-4">
                                        <div className="font-bold text-gray-800 mb-2">4. Rincian dan lokasi</div>
                                        <div className="text-xs text-gray-600 space-y-1">
                                            <div><strong>Lokasi:</strong> {form.data.lokasi_kegiatan} {form.data.latitude ? `(${form.data.latitude}, ${form.data.longitude})` : ''}</div>
                                            <div><strong>Rincian kegiatan:</strong> {form.data.deskripsi_kegiatan}</div>
                                        </div>
                                    </div>

                                    <div className="rounded-xl border border-gray-100 bg-[#f9fafb] p-4">
                                        <div className="font-bold text-gray-800 mb-2">5. Dokumentasi dan 6. Luaran</div>
                                        <div className="text-xs text-gray-600 space-y-1">
                                            <div><strong>Foto kegiatan:</strong> {form.data.foto_kegiatan.length ? `${form.data.foto_kegiatan.length} berkas baru dipilih` : (log?.files?.some((file) => file.jenis_file === 'foto_kegiatan') ? 'Tersimpan' : 'Belum diunggah')}</div>
                                            <div><strong>Deskripsi luaran:</strong> {form.data.deskripsi_luaran}</div>
                                            <div><strong>Berkas luaran:</strong> {form.data.file_luaran.length ? `${form.data.file_luaran.length} berkas baru dipilih` : (log?.files?.some((file) => file.jenis_file === 'file_luaran') ? 'Tersimpan' : 'Belum diunggah')}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Navigation Buttons Footer */}
                        <div className="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
                            {/* Left: Save Draft */}
                            <button
                                type="button"
                                disabled={form.processing}
                                onClick={handleSaveDraft}
                                className="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 transition cursor-pointer"
                            >
                                {form.processing ? 'Menyimpan...' : 'Simpan draf'}
                            </button>

                            {/* Right: Back & Continue/Submit */}
                            <div className="flex items-center gap-3">
                                {activeStep > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setStepValidationError(null);
                                            setActiveStep(activeStep - 1);
                                        }}
                                        className="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 transition cursor-pointer"
                                    >
                                        Kembali
                                    </button>
                                )}

                                {activeStep < 7 ? (
                                    <button
                                        type="button"
                                        disabled={form.processing}
                                        onClick={handleContinue}
                                        className="inline-flex items-center gap-2 rounded-xl bg-[#064e3b] hover:bg-[#065f46] px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition cursor-pointer"
                                    >
                                        <span>Lanjut</span>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        disabled={form.processing}
                                        onClick={handleFinalSubmit}
                                        className="inline-flex items-center gap-2 rounded-xl bg-[#064e3b] hover:bg-[#065f46] px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition cursor-pointer"
                                    >
                                        <span>Kirim kegiatan</span>
                                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
