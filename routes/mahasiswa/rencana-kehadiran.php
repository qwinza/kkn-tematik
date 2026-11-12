<?php

use App\Http\Controllers\Mahasiswa\RencanaKehadiranController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'mahasiswa'])
    ->prefix('mahasiswa')
    ->name('mahasiswa.')
    ->group(function () {

        // Halaman utama Schedule mahasiswa
        Route::get('/schedule', [RencanaKehadiranController::class, 'index'])
            ->name('schedule.index');

        Route::post('/schedule', [RencanaKehadiranController::class, 'store'])
            ->name('schedule.store');

        Route::put('/schedule/{rencanaKehadiran}', [RencanaKehadiranController::class, 'update'])
            ->name('schedule.update');

        // Kompatibilitas dengan URL lama
        Route::get('/rencana-kehadiran', [RencanaKehadiranController::class, 'index'])
            ->name('rencana-kehadiran.index');

        Route::post('/rencana-kehadiran', [RencanaKehadiranController::class, 'store'])
            ->name('rencana-kehadiran.store');

        Route::put('/rencana-kehadiran/{rencanaKehadiran}', [RencanaKehadiranController::class, 'update'])
            ->name('rencana-kehadiran.update');
    });
