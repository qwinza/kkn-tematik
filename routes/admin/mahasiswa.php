<?php

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
});
