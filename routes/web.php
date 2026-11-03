<?php

use App\Http\Controllers\LogKegiatanController;
use App\Http\Controllers\MasterKegiatanController;
use App\Http\Controllers\ProdiController;
use App\Http\Controllers\ProfileController;
use App\Models\LogKegiatan;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $pesertaId = $user?->peserta?->id;
    $logs = LogKegiatan::query()->where('peserta_id', $pesertaId ?? 0);

    return Inertia::render('Dashboard', [
        'summary' => [
            'totalKegiatan' => (clone $logs)->count(),
            'totalDisetujui' => (clone $logs)->where('status', 'disetujui')->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

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
