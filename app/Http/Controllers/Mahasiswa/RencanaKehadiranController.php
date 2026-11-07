<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RencanaKehadiranController extends Controller
{
    /**
     * Sesi kehadiran yang valid.
     */
    public const VALID_SESSIONS = [
        '07:00 - 10:00 (Pagi)',
        '10:00 - 13:00 (Siang)',
        '13:00 - 16:00 (Sore)',
    ];

    /**
     * Menampilkan profil mahasiswa dan riwayat rencana kehadiran miliknya.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        /** @var Peserta|null $peserta */
        $peserta = $user->peserta()->with('prodi')->first();

        if (! $peserta) {
            abort(404, 'Data peserta mahasiswa tidak ditemukan.');
        }

        $prodiDetail = $peserta->prodi ? [
            'kode_prodi' => $peserta->prodi->kode_prodi,
            'nama_prodi' => $peserta->prodi->nama_prodi,
            'fakultas' => $peserta->prodi->fakultas,
        ] : null;

        $namaProdi = $peserta->prodi?->nama_prodi
            ?? $peserta->program_studi;

        $pesertaData = [
            'id' => $peserta->id,
            'nama_lengkap' => $peserta->nama_lengkap,
            'nim' => $peserta->nim,
            'program_studi' => $namaProdi,
            'program_studi_detail' => $prodiDetail,
        ];

        $rencanaKehadirans = $peserta->rencanaKehadirans()
            ->with('logKegiatans.masterKegiatan')
            ->orderByDesc('tanggal_rencana')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Mahasiswa/RencanaKehadiran/Index', [
            'peserta' => $pesertaData,
            'rencanaKehadirans' => $rencanaKehadirans,
            'sessions' => self::VALID_SESSIONS,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Menyimpan rencana kehadiran baru milik mahasiswa terautentikasi.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_rencana' => ['required', 'date', 'after_or_equal:today'],
            'jam_kehadiran' => ['required', Rule::in(self::VALID_SESSIONS)],
        ]);

        $user = $request->user();

        /** @var Peserta|null $peserta */
        $peserta = $user->peserta;

        if (! $peserta) {
            abort(404, 'Data peserta mahasiswa tidak ditemukan.');
        }

        $peserta->rencanaKehadirans()->create([
            'tanggal_rencana' => $validated['tanggal_rencana'],
            'jam_kehadiran' => $validated['jam_kehadiran'],
        ]);

        $redirectRoute = request()->routeIs('mahasiswa.schedule.store')
            ? 'mahasiswa.schedule.index'
            : 'mahasiswa.rencana-kehadiran.index';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Rencana kehadiran berhasil disimpan.');
    }
}
