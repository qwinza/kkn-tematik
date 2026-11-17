<?php

use App\Http\Controllers\Admin\DppManagementController;
use App\Http\Controllers\Admin\MahasiswaManagementController;
use App\Http\Controllers\Admin\MahasiswaMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/mahasiswa', [MahasiswaMonitoringController::class, 'index'])
        ->name('mahasiswa.index');

    // CRUD Mahasiswa
    Route::post('/mahasiswa', [MahasiswaManagementController::class, 'store'])
        ->name('mahasiswa.store');

    Route::put('/mahasiswa/{peserta}', [MahasiswaManagementController::class, 'update'])
        ->name('mahasiswa.update');

    Route::delete('/mahasiswa/{peserta}', [MahasiswaManagementController::class, 'destroy'])
        ->name('mahasiswa.destroy');

    // Detail Mahasiswa
    Route::get('/mahasiswa/{peserta}', [MahasiswaMonitoringController::class, 'show'])
        ->name('mahasiswa.show');

    Route::get('/dpp', [DppManagementController::class, 'index'])->name('dpp.index');
    Route::post('/dpp', [DppManagementController::class, 'store'])->name('dpp.store');
    Route::put('/dpp/{user}', [DppManagementController::class, 'update'])->name('dpp.update');
    Route::put('/dpp/{user}/password', [DppManagementController::class, 'resetPassword'])->name('dpp.reset-password');
    Route::delete('/dpp/{user}', [DppManagementController::class, 'destroy'])->name('dpp.destroy');
});
