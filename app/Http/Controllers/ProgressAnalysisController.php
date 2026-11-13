<?php

namespace App\Http\Controllers;

use App\Models\LogKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Services\PythonAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ProgressAnalysisController extends Controller
{
    public function index(Request $request, PythonAnalyticsService $python): Response
    {
        $user = $request->user();
        $isDpp = $user?->isDpp() ?? false;
        abort_unless($user && ($user->isAdmin() || $isDpp), 403);

        $dppKodeProdi = $isDpp ? $user->dppProfile?->kode_prodi : null;
        $dppNamaProdi = $isDpp
            ? Prodi::query()->where('kode_prodi', $dppKodeProdi)->value('nama_prodi')
            : null;

        if ($isDpp) {
            abort_unless($dppKodeProdi, 403, 'Akun DPP belum terhubung dengan program studi.');
        }

        $filters = $request->validate([
            'kode_prodi' => [$isDpp ? 'prohibited' : 'nullable', 'string', 'exists:prodi,kode_prodi'],
        ]);
        $selectedKodeProdi = $isDpp ? $dppKodeProdi : ($filters['kode_prodi'] ?? null);
        $selectedNamaProdi = $selectedKodeProdi
            ? Prodi::query()->where('kode_prodi', $selectedKodeProdi)->value('nama_prodi')
            : null;

        $studentsQuery = Peserta::query()
            ->with('prodi')
            ->whereHas('user', fn ($query) => $query->where('role', 'mahasiswa'));

        if ($isDpp) {
            $studentsQuery->where(function ($query) use ($dppKodeProdi, $dppNamaProdi): void {
                $query->where('kode_prodi', $dppKodeProdi)
                    ->orWhere(function ($legacyQuery) use ($dppNamaProdi): void {
                        $legacyQuery->whereNull('kode_prodi')
                            ->where('program_studi', $dppNamaProdi);
                    });
            });
        } elseif ($selectedKodeProdi) {
            $studentsQuery->where(function ($query) use ($selectedKodeProdi, $selectedNamaProdi): void {
                $query->where('kode_prodi', $selectedKodeProdi)
                    ->orWhere(function ($legacyQuery) use ($selectedNamaProdi): void {
                        $legacyQuery->whereNull('kode_prodi')
                            ->where('program_studi', $selectedNamaProdi);
                    });
            });
        }

        $students = $studentsQuery->get()->map(fn (Peserta $peserta) => [
            'nim' => $peserta->nim,
            'kode_prodi' => $peserta->kode_prodi
                ?? ($isDpp ? $dppKodeProdi : ($peserta->program_studi ? 'legacy:'.$peserta->program_studi : null)),
            'prodi' => $peserta->prodi?->nama_prodi ?? ($isDpp ? $dppNamaProdi : $peserta->program_studi),
        ])->all();

        $activitiesQuery = LogKegiatan::query()
            ->where('status', 'disetujui')
            ->whereHas('peserta.user', fn ($query) => $query->where('role', 'mahasiswa'))
            ->with(['peserta.prodi', 'masterKegiatan']);

        if ($isDpp) {
            $activitiesQuery->whereHas('peserta', function ($query) use ($dppKodeProdi, $dppNamaProdi): void {
                $query->where(function ($prodiQuery) use ($dppKodeProdi, $dppNamaProdi): void {
                    $prodiQuery->where('kode_prodi', $dppKodeProdi)
                        ->orWhere(function ($legacyQuery) use ($dppNamaProdi): void {
                            $legacyQuery->whereNull('kode_prodi')
                                ->where('program_studi', $dppNamaProdi);
                        });
                });
            });
        } elseif ($selectedKodeProdi) {
            $activitiesQuery->whereHas('peserta', function ($query) use ($selectedKodeProdi, $selectedNamaProdi): void {
                $query->where(function ($prodiQuery) use ($selectedKodeProdi, $selectedNamaProdi): void {
                    $prodiQuery->where('kode_prodi', $selectedKodeProdi)
                        ->orWhere(function ($legacyQuery) use ($selectedNamaProdi): void {
                            $legacyQuery->whereNull('kode_prodi')
                                ->where('program_studi', $selectedNamaProdi);
                        });
                });
            });
        }

        $activities = $activitiesQuery->get()->map(fn (LogKegiatan $log) => [
            'nim' => $log->peserta?->nim,
            'kode_prodi' => $log->peserta?->kode_prodi
                ?? ($isDpp ? $dppKodeProdi : ($log->peserta?->program_studi ? 'legacy:'.$log->peserta->program_studi : null)),
            'prodi' => $log->peserta?->prodi?->nama_prodi ?? ($isDpp ? $dppNamaProdi : $log->peserta?->program_studi),
            'nama_kegiatan' => $log->masterKegiatan?->nama_kegiatan ?? 'Kegiatan lainnya',
            'durasi_jam' => $log->durasiJam(),
        ])->all();

        try {
            $analysis = $python->run('analisis-progres', [
                'mahasiswa' => $students,
                'kegiatan' => $activities,
            ]);
        } catch (RuntimeException $exception) {
            Log::error('Python KKN progress analysis failed.', ['exception' => $exception]);
            abort(503, 'Analisis Python belum tersedia. Pastikan Python terpasang di server.');
        }

        return Inertia::render('ProgressAnalysis', [
            'analysis' => $analysis,
            'isDpp' => $isDpp,
            'dppProdi' => $dppNamaProdi,
            'prodis' => $isDpp ? [] : Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi']),
            'selectedKodeProdi' => $selectedKodeProdi,
            'selectedNamaProdi' => $selectedNamaProdi,
        ]);
    }
}
