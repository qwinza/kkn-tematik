<?php

use App\Http\Controllers\LogKegiatanController;
use App\Http\Controllers\MasterKegiatanController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProfileController;
use App\Models\LogKegiatan;
use App\Models\Peserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $isDosen = $user?->isDosen() ?? false;
    $dosenProdiName = $isDosen ? $user->prodi?->nama_prodi : null;

    $pesertaId = $user?->peserta?->id;
    $logs = LogKegiatan::query()
        ->when($isDosen, fn ($query) => $query->whereHas('peserta', fn ($pesertaQuery) => $pesertaQuery
            ->where('kode_prodi', $user->kode_prodi)
            ->orWhere('program_studi', $dosenProdiName)))
        ->when(! $isAdmin && ! $isDosen, fn ($query) => $query->where('peserta_id', $pesertaId ?? 0));

    $totalJamDisetujui = round(
        (clone $logs)->where('status', 'disetujui')->get()->sum(fn (LogKegiatan $log) => $log->durasiJam()),
        1
    );

    $summary = [
        'totalKegiatan' => (clone $logs)->count(),
        'totalDisetujui' => (clone $logs)->where('status', 'disetujui')->count(),
        'totalLapangan' => (clone $logs)->where('lokasi_kegiatan', 'Lapangan')->count(),
        'totalKampus' => (clone $logs)->where('lokasi_kegiatan', 'Kampus ITENAS')->count(),
        'totalJamDisetujui' => $totalJamDisetujui,
    ];

    $analytics = null;

    if ($isAdmin || $isDosen) {
        if ($isDosen) {
            $managedStudentCount = Peserta::query()
                ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
                ->where(fn ($query) => $query
                    ->where('kode_prodi', $user->kode_prodi)
                    ->orWhere('program_studi', $dosenProdiName))
                ->count();

            $studentsPerFaculty = collect([[
                'fakultas' => $user->prodi?->fakultas ?: 'Belum ditentukan',
                'total' => $managedStudentCount,
            ]]);
        } else {
            $studentsPerFaculty = Peserta::query()
                ->join('users', 'users.id', '=', 'pesertas.user_id')
                ->leftJoin('prodi', 'prodi.kode_prodi', '=', 'pesertas.kode_prodi')
                ->where('users.role', 'mahasiswa')
                ->selectRaw("COALESCE(NULLIF(prodi.fakultas, ''), 'Belum ditentukan') as fakultas, COUNT(pesertas.id) as total")
                ->groupBy('fakultas')
                ->orderBy('fakultas')
                ->get();
        }

        $reportsByStatus = LogKegiatan::query()
            ->whereIn('status', ['disetujui', 'menunggu', 'ditolak'])
            ->when($isDosen, fn ($query) => $query->whereHas('peserta', fn ($pesertaQuery) => $pesertaQuery
                ->where('kode_prodi', $user->kode_prodi)
                ->orWhere('program_studi', $dosenProdiName)))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $analytics = [
            'totalMahasiswa' => Peserta::query()
                ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
                ->when($isDosen, fn ($query) => $query->where(fn ($query) => $query
                    ->where('kode_prodi', $user->kode_prodi)
                    ->orWhere('program_studi', $dosenProdiName)))
                ->count(),
            'totalLaporan' => $reportsByStatus->sum(),
            'totalLaporanDisetujui' => $reportsByStatus->get('disetujui', 0),
            'totalLaporanMenunggu' => $reportsByStatus->get('menunggu', 0),
            'totalLaporanDitolak' => $reportsByStatus->get('ditolak', 0),
            'mahasiswaPerFakultas' => $studentsPerFaculty,
        ];
    }

    return Inertia::render('Dashboard', [
        'summary' => $summary,
        'analytics' => $analytics,
        'managedProdi' => $isDosen ? $user->prodi?->nama_prodi : null,
    ]);
})->middleware(['auth'])->name('dashboard');

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
        ->middleware('admin')
        ->name('log-kegiatan.export');
    Route::post('/log-kegiatan/approve-all', [LogKegiatanController::class, 'approveAll'])
        ->middleware('admin')
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
require __DIR__.'/dosen.php';
require __DIR__.'/mahasiswa/rencana-kehadiran.php';
