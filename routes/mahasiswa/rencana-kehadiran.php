<?php

use App\Http\Controllers\Mahasiswa\RencanaKehadiranController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'mahasiswa'])->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
    Route::get('/rencana-kehadiran', [RencanaKehadiranController::class, 'index'])->name('rencana-kehadiran.index');
    Route::post('/rencana-kehadiran', [RencanaKehadiranController::class, 'store'])->name('rencana-kehadiran.store');
});
