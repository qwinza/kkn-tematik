<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MahasiswaMonitoringController extends Controller
{
    /**
     * Menampilkan daftar monitoring seluruh mahasiswa KKN.
     */
    public function index(Request $request): Response
    {
        $isDpp = $request->user()->isDpp();
        abort_unless($isDpp || $request->user()->isAdmin(), 403);

        $dppKodeProdi = $isDpp ? $request->user()->dppProfile?->kode_prodi : null;
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
            'sort' => ['nullable', Rule::in(['id', 'nama_lengkap', 'nim', 'prodi', 'fakultas'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Peserta::query()
            ->with(['user', 'prodi', 'logKegiatans' => fn ($query) => $query->where('status', 'disetujui')])
            ->whereHas('user', function ($q) {
                $q->where('role', 'mahasiswa');
            });

        if ($isDpp) {
            $query->where('kode_prodi', $dppKodeProdi ?? '');
        }

        // Filter pencarian berdasarkan nama lengkap, NIM, atau nama akun user
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nim', 'like', "%{$search}%")
                    ->orWhere('program_studi', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('prodi', function ($prodiQuery) use ($search) {
                        $prodiQuery->where('nama_prodi', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters['kode_prodi'] ?? null) {
            $prodiName = Prodi::query()
                ->where('kode_prodi', $filters['kode_prodi'])
                ->value('nama_prodi');

            $query->where(function ($prodiQuery) use ($filters, $prodiName) {
                $prodiQuery->where('kode_prodi', $filters['kode_prodi'])
                    ->orWhere('program_studi', $prodiName);
            });
        }

        $totalCountQuery = Peserta::whereHas('user', function ($q) {
            $q->where('role', 'mahasiswa');
        });

        if ($isDpp) {
            $totalCountQuery->where('kode_prodi', $dppKodeProdi ?? '');
        }

        $totalCount = $totalCountQuery->count();

        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        if ($sort === 'prodi') {
            $query->orderBy(Prodi::query()
                ->select('nama_prodi')
                ->whereColumn('prodi.kode_prodi', 'pesertas.kode_prodi'), $direction);
        } elseif ($sort === 'fakultas') {
            $query->orderBy(Prodi::query()
                ->select('fakultas')
                ->whereColumn('prodi.kode_prodi', 'pesertas.kode_prodi'), $direction);
        } elseif ($sort !== 'id') {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('id');
        }

        $mahasiswas = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Peserta $peserta) => $this->transformPeserta($peserta));

        $prodis = Prodi::orderBy('nama_prodi')
            ->get(['kode_prodi', 'nama_prodi', 'fakultas']);

        return Inertia::render('Admin/Mahasiswa/Index', [
            'mahasiswas' => $mahasiswas,
            'prodis' => $prodis,
            'filters' => [
                ...$request->only(['search', 'kode_prodi']),
                'sort' => $sort,
                'direction' => $direction,
            ],
            'totalCount' => $totalCount,
            'isDpp' => $isDpp,
            'canManage' => ! $isDpp,
        ]);
    }

    /**
     * Menampilkan detail profil mahasiswa secara individual.
     */
    public function show(Request $request, Peserta $peserta): Response
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isDpp(), 403);

        $peserta->load(['user', 'prodi', 'logKegiatans' => fn ($query) => $query->where('status', 'disetujui')]);

        // Pastikan hanya profil mahasiswa yang dapat diakses
        if (! $peserta->user || ! $peserta->user->isMahasiswa()) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        if ($user->isDpp()) {
            abort_unless($peserta->kode_prodi === $user->dppProfile?->kode_prodi, 404);
        }

        return Inertia::render('Admin/Mahasiswa/Show', [
            'mahasiswa' => $this->transformPeserta($peserta),
            'isDpp' => $user->isDpp(),
        ]);
    }

    /**
     * Serialisasi eksplisit data peserta mahasiswa ke bentuk array yang konsisten.
     *
     * @return array<string, mixed>
     */
    protected function transformPeserta(Peserta $peserta): array
    {
        $prodi = $peserta->prodi ? [
            'kode_prodi' => $peserta->prodi->kode_prodi,
            'nama_prodi' => $peserta->prodi->nama_prodi,
            'fakultas' => $peserta->prodi->fakultas,
        ] : null;

        $legacyProdi = $peserta->program_studi ?? $peserta->prodi?->nama_prodi;

        return [
            'id' => $peserta->id,
            'route_key' => $peserta->getRouteKey(),
            'user_id' => $peserta->user_id,
            'nama_lengkap' => $peserta->nama_lengkap,
            'nim' => $peserta->nim,
            'program_studi' => $legacyProdi,
            'kode_prodi' => $peserta->kode_prodi,
            'total_jam_disetujui' => round($peserta->logKegiatans->sum(fn ($log) => $log->durasiJam()), 1),
            'prodi' => $prodi,
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
