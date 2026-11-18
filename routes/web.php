<?php

use App\Http\Controllers\LogKegiatanController;
use App\Http\Controllers\MasterKegiatanController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressAnalysisController;
use App\Models\LogKegiatan;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $isDpp = $user?->isDpp() ?? false;

    $dppProdi = $isDpp
        ? $user->dppProfile?->prodi
        : null;

    abort_unless(
        ! $isDpp || $dppProdi,
        403,
        'Profil DPP belum memiliki program studi.'
    );

    $dppKodeProdi = $dppProdi?->kode_prodi;
    $dppNamaProdi = $dppProdi?->nama_prodi;

    $constrainDppProdi = function ($query) use (
        $dppKodeProdi,
        $dppNamaProdi
    ): void {
        $query->where(function ($prodiQuery) use (
            $dppKodeProdi,
            $dppNamaProdi
        ) {
            $prodiQuery->where('pesertas.kode_prodi', $dppKodeProdi)
                ->orWhere(function ($legacyQuery) use ($dppNamaProdi) {
                    $legacyQuery
                        ->whereNull('pesertas.kode_prodi')
                        ->where('pesertas.program_studi', $dppNamaProdi);
                });
        });
    };

    $pesertaId = $user?->peserta?->id;

    $logs = LogKegiatan::query()
        ->when(
            $isDpp,
            fn ($query) => $query->whereHas(
                'peserta',
                $constrainDppProdi
            )
        )
        ->when(
            ! $user?->isAdmin() && ! $isDpp,
            fn ($query) => $query->where('peserta_id', $pesertaId ?? 0)
        );

    $summary = [
        'totalKegiatan' => (clone $logs)->count(),
        'totalDisetujui' => (clone $logs)->where('status', 'disetujui')->count(),
        'totalLapangan' => (clone $logs)->where('lokasi_kegiatan', 'Lapangan')->count(),
        'totalKampus' => (clone $logs)->where('lokasi_kegiatan', 'Kampus ITENAS')->count(),
    ];

    $totalJamDisetujui = round(
        (clone $logs)->where('status', 'disetujui')
            ->get()
            ->sum(fn ($log) => $log->durasiJam()),
        1
    );

    $summary['totalJamDisetujui'] = $totalJamDisetujui;

    $studentsPerFacultyQuery = Peserta::query()
        ->join('users', 'users.id', '=', 'pesertas.user_id')
        ->leftJoin('prodi', 'prodi.kode_prodi', '=', 'pesertas.kode_prodi')
        ->where('users.role', 'mahasiswa');

    if ($isDpp) {
        $studentsPerFacultyQuery->where($constrainDppProdi);
    }

    $studentsPerFaculty = $studentsPerFacultyQuery
        ->selectRaw(
            "COALESCE(NULLIF(prodi.fakultas, ''), ?) as fakultas, COUNT(pesertas.id) as total",
            [$dppProdi?->fakultas ?: 'Belum ditentukan']
        )
        ->groupBy('fakultas')
        ->orderBy('fakultas')
        ->get();

    $reportsByStatus = LogKegiatan::query()
        ->whereHas('peserta.user', fn ($query) => $query->where('role', 'mahasiswa'))
        ->whereIn('status', ['disetujui', 'menunggu', 'ditolak'])
        ->when(
            $isDpp,
            fn ($query) => $query->whereHas('peserta', $constrainDppProdi)
        )
        ->select('status', DB::raw('COUNT(*) as total'))
        ->groupBy('status')
        ->pluck('total', 'status');

    $analytics = [
        'totalMahasiswa' => $studentsPerFaculty->sum('total'),
        'mahasiswaPerFakultas' => $studentsPerFaculty,
        'totalLaporan' => $reportsByStatus->sum(),
        'totalLaporanDisetujui' => $reportsByStatus->get('disetujui', 0),
        'totalLaporanMenunggu' => $reportsByStatus->get('menunggu', 0),
        'totalLaporanDitolak' => $reportsByStatus->get('ditolak', 0),
    ];

    if ($isDpp) {
        $summary['totalMahasiswa'] = Peserta::query()
            ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
            ->where($constrainDppProdi)
            ->count();
    }

    return Inertia::render('Dashboard', [
        'summary' => $summary,
        'dppProdi' => $dppProdi?->nama_prodi,
        'analytics' => $analytics,
    ]);
})->middleware(['auth'])->name('dashboard');

Route::get('/analisis-progres-kkn', [ProgressAnalysisController::class, 'index'])
    ->middleware(['auth'])
    ->name('progress-analysis.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/prodi', [ProdiController::class, 'index'])->name('prodi.index');
    Route::post('/prodi', [ProdiController::class, 'store'])->name('prodi.store');
    Route::match(['put', 'patch'], '/prodi/{prodi}', [ProdiController::class, 'update'])->name('prodi.update');
    Route::delete('/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('prodi.destroy');

    // Master Kegiatan CRUD
    Route::get('/master-kegiatan', [MasterKegiatanController::class, 'index'])->name('master-kegiatan.index');
    Route::post('/master-kegiatan', [MasterKegiatanController::class, 'store'])->name('master-kegiatan.store');
    Route::match(['put', 'patch'], '/master-kegiatan/{master_kegiatan}', [MasterKegiatanController::class, 'update'])->name('master-kegiatan.update');
    Route::delete('/master-kegiatan/{master_kegiatan}', [MasterKegiatanController::class, 'destroy'])->name('master-kegiatan.destroy');
    Route::patch('/master-kegiatan/{master_kegiatan}/status', [MasterKegiatanController::class, 'updateStatus'])->name('master-kegiatan.status');

    // Log Kegiatan CRUD & Stepper Handlers
    Route::get('/log-kegiatan', [LogKegiatanController::class, 'index'])->name('log-kegiatan.index');
    Route::get('/log-kegiatan/create', [LogKegiatanController::class, 'create'])->name('log-kegiatan.create');
    Route::post('/log-kegiatan/save-step', [LogKegiatanController::class, 'saveStep'])->name('log-kegiatan.save-step.create');
    Route::post('/log-kegiatan/{log_kegiatan}/save-step', [LogKegiatanController::class, 'saveStep'])->name('log-kegiatan.save-step');
    Route::post('/log-kegiatan/{log_kegiatan}/approve', [LogKegiatanController::class, 'approve'])->name('log-kegiatan.approve');
    Route::post('/log-kegiatan/{log_kegiatan}/reject', [LogKegiatanController::class, 'reject'])->name('log-kegiatan.reject');
    Route::delete('/log-kegiatan/{log_kegiatan}', [LogKegiatanController::class, 'destroy'])->name('log-kegiatan.destroy');
    Route::get('/log-kegiatan/export', [LogKegiatanController::class, 'export'])
        ->name('log-kegiatan.export');
    Route::get('/log-kegiatan/export-rekap-jam-python', [LogKegiatanController::class, 'exportPythonSummary'])
        ->name('log-kegiatan.export-python-summary');

    Route::get('/log-kegiatan/export-pdf', [LogKegiatanController::class, 'exportStudentPdf'])
        ->name('log-kegiatan.export-student-pdf');

    Route::post('/log-kegiatan/approve-all', [LogKegiatanController::class, 'approveAll'])
        ->name('log-kegiatan.approve-all');
    Route::post('/log-kegiatan/step-1', [LogKegiatanController::class, 'saveStep1'])->name('log-kegiatan.step-1');
    Route::post('/log-kegiatan/{log_kegiatan}/step-1', [LogKegiatanController::class, 'saveStep1'])->name('log-kegiatan.step-1.update');
    Route::post('/log-kegiatan/{log_kegiatan}/step-2', [LogKegiatanController::class, 'saveStep2'])->name('log-kegiatan.step-2');
    Route::post('/log-kegiatan/{log_kegiatan}/step-3', [LogKegiatanController::class, 'saveStep3'])->name('log-kegiatan.step-3');
    Route::get('/log-kegiatan/{log_kegiatan}', [LogKegiatanController::class, 'show'])->name('log-kegiatan.show');
    Route::get('/log-kegiatan/{log_kegiatan}/files/{file}', [LogKegiatanController::class, 'showFile'])->name('log-kegiatan.files.show');
    Route::get('/log-kegiatan/{log_kegiatan}/edit', [LogKegiatanController::class, 'edit'])->name('log-kegiatan.edit');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin/mahasiswa.php';
require __DIR__.'/mahasiswa/rencana-kehadiran.php';
