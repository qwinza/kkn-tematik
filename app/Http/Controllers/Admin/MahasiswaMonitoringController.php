<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MahasiswaMonitoringController extends Controller
{
    /**
     * Menampilkan daftar monitoring seluruh mahasiswa KKN.
     */
    public function index(Request $request): Response
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'program_studi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
        ]);

        $query = Peserta::query()
            ->with(['user', 'programStudi'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'mahasiswa');
            });

        // Filter pencarian berdasarkan nama lengkap, NIM, atau nama akun user
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan Program Studi
        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->input('program_studi_id'));
        }

        $totalCount = Peserta::whereHas('user', function ($q) {
            $q->where('role', 'mahasiswa');
        })->count();

        $mahasiswas = $query->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Peserta $peserta) => $this->transformPeserta($peserta));

        $programStudis = ProgramStudi::orderBy('nama_prodi')
            ->get(['id', 'nama_prodi', 'fakultas']);

        return Inertia::render('Admin/Mahasiswa/Index', [
            'mahasiswas' => $mahasiswas,
            'programStudis' => $programStudis,
            'filters' => $request->only(['search', 'program_studi_id']),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * Menampilkan detail profil mahasiswa secara individual.
     */
    public function show(Peserta $peserta): Response
    {
        $peserta->load(['user', 'programStudi']);

        // Pastikan hanya profil mahasiswa yang dapat diakses
        if (! $peserta->user || ! $peserta->user->isMahasiswa()) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        return Inertia::render('Admin/Mahasiswa/Show', [
            'mahasiswa' => $this->transformPeserta($peserta),
        ]);
    }

    /**
     * Serialisasi eksplisit data peserta mahasiswa ke bentuk array yang konsisten.
     *
     * @return array<string, mixed>
     */
    protected function transformPeserta(Peserta $peserta): array
    {
        $programStudiDetail = $peserta->programStudi ? [
            'id' => $peserta->programStudi->id,
            'nama_prodi' => $peserta->programStudi->nama_prodi,
            'fakultas' => $peserta->programStudi->fakultas,
        ] : null;

        $legacyProdi = $peserta->program_studi ?? $peserta->programStudi?->nama_prodi;

        return [
            'id' => $peserta->id,
            'user_id' => $peserta->user_id,
            'nama_lengkap' => $peserta->nama_lengkap,
            'nim' => $peserta->nim,
            'program_studi' => $legacyProdi,
            'program_studi_id' => $peserta->program_studi_id,
            'program_studi_detail' => $programStudiDetail,
            'user' => $peserta->user ? [
                'id' => $peserta->user->id,
                'name' => $peserta->user->name,
                'email' => $peserta->user->email,
                'nim' => $peserta->user->nim,
            ] : null,
            'created_at' => $peserta->created_at,
            'updated_at' => $peserta->updated_at,
        ];
    }
}
