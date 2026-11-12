<?php

use App\Http\Controllers\Admin\MahasiswaManagementController;
use App\Http\Controllers\Admin\MahasiswaMonitoringController;
use App\Models\LogKegiatan;
use App\Models\Peserta;
use App\Http\Controllers\Admin\DppManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/analitik', function () {
        $studentsPerFaculty = Peserta::query()
            ->join('users', 'users.id', '=', 'pesertas.user_id')
            ->leftJoin('prodi', 'prodi.kode_prodi', '=', 'pesertas.kode_prodi')
            ->where('users.role', 'mahasiswa')
            ->selectRaw("COALESCE(NULLIF(prodi.fakultas, ''), 'Belum ditentukan') as fakultas, COUNT(pesertas.id) as total")
            ->groupBy('fakultas')
            ->orderBy('fakultas')
            ->get();

        $reportsByStatus = LogKegiatan::query()
            ->whereIn('status', ['disetujui', 'menunggu', 'ditolak'])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Analytics', [
            'summary' => [
                'totalMahasiswa' => Peserta::query()
                    ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'))
                    ->count(),
                'totalLaporan' => $reportsByStatus->sum(),
                'totalDisetujui' => $reportsByStatus->get('disetujui', 0),
                'totalMenunggu' => $reportsByStatus->get('menunggu', 0),
                'totalDitolak' => $reportsByStatus->get('ditolak', 0),
                'mahasiswaPerFakultas' => $studentsPerFaculty,
            ],
        ]);
    })->name('analytics');

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
    // Manajemen DPP
    Route::get('/dpp', [DppManagementController::class, 'index'])
        ->name('dpp.index');

    Route::post('/dpp', [DppManagementController::class, 'store'])
        ->name('dpp.store');

    Route::put('/dpp/{user}', [DppManagementController::class, 'update'])
        ->name('dpp.update');

    Route::put('/dpp/{user}/password', [DppManagementController::class, 'resetPassword'])
        ->name('dpp.reset-password');

    Route::delete('/dpp/{user}', [DppManagementController::class, 'destroy'])
        ->name('dpp.destroy');
});
