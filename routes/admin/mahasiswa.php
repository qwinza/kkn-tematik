<?php

use App\Http\Controllers\Admin\MahasiswaMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/mahasiswa', [MahasiswaMonitoringController::class, 'index'])->name('mahasiswa.index');
    Route::get('/mahasiswa/{peserta}', [MahasiswaMonitoringController::class, 'show'])->name('mahasiswa.show');
});
