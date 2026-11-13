<?php

use App\Http\Controllers\LogKegiatanController;
use App\Http\Controllers\MasterKegiatanController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProfileController;
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
    $dppKodeProdi = $isDpp ? $user->dppProfile?->kode_prodi : null;

    $pesertaId = $user?->peserta?->id;
    $logs = LogKegiatan::query()
        ->when($isDpp, fn ($query) => $query->whereHas('peserta', fn ($pesertaQuery) => $pesertaQuery->where('kode_prodi', $dppKodeProdi ?? '')))
        ->when(! $user?->isAdmin() && ! $isDpp, fn ($query) => $query->where('peserta_id', $pesertaId ?? 0));

    $summary = [
        'totalKegiatan' => (clone $logs)->count(),
        'totalDisetujui' => (clone $logs)->where('status', 'disetujui')->count(),
        'totalLapangan' => (clone $logs)->where('lokasi_kegiatan', 'Lapangan')->count(),
        'totalKampus' => (clone $logs)->where('lokasi_kegiatan', 'Kampus ITENAS')->count(),
    ];

    $totalJamDisetujui = round(
        (clone $logs)->where('status', 'disetujui')->get()->sum(fn (LogKegiatan $log) => $log->durasiJam()),
        1
    );

    $summary['totalJamDisetujui'] = $totalJamDisetujui;

    if ($isDpp) {
        $summary['totalMahasiswa'] = Peserta::query()
            ->where('kode_prodi', $dppKodeProdi ?? '')
            ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
            ->count();
    }

    return Inertia::render('Dashboard', [
        'summary' => $summary,
        'dppProdi' => $isDpp ? $user->dppProfile?->prodi?->nama_prodi : null,
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
    Route::delete('/master-kegiatan/{master_kegiatan}', [MasterKegiatanController::class, 'destroy'])->name('master-kegiatan.destroy');

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
