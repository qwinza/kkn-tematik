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
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $isDosen = $user?->isDosen() ?? false;
        $dosenProdiName = $isDosen ? $user->prodi?->nama_prodi : null;

        abort_unless($isAdmin || ($isDosen && $user->prodi()->exists()), 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
            'sort' => ['nullable', Rule::in(['id', 'nama_lengkap', 'nim', 'prodi', 'fakultas'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        if ($isDosen) {
            $filters['kode_prodi'] = $user->kode_prodi;
        }

        $query = Peserta::query()
            ->with(['user', 'prodi'])
            ->when($isDosen, fn ($query) => $query->where(function ($prodiQuery) use ($user, $dosenProdiName): void {
                $prodiQuery->where('kode_prodi', $user->kode_prodi)
                    ->orWhere('program_studi', $dosenProdiName);
            }))
            ->whereHas('user', function ($q) {
                $q->where('role', 'mahasiswa');
            });

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

        $totalCount = Peserta::query()
            ->whereHas('user', fn ($q) => $q->where('role', 'mahasiswa'))
            ->when($isDosen, fn ($query) => $query->where(function ($prodiQuery) use ($user, $dosenProdiName): void {
                $prodiQuery->where('kode_prodi', $user->kode_prodi)
                    ->orWhere('program_studi', $dosenProdiName);
            }))
            ->count();

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

        $prodis = $isDosen
            ? $user->prodi()->get(['kode_prodi', 'nama_prodi', 'fakultas'])
            : Prodi::orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi', 'fakultas']);

        return Inertia::render('Admin/Mahasiswa/Index', [
            'mahasiswas' => $mahasiswas,
            'prodis' => $prodis,
            'filters' => [
                ...$request->only(['search', 'kode_prodi']),
                'sort' => $sort,
                'direction' => $direction,
            ],
            'totalCount' => $totalCount,
            'canManage' => $isAdmin,
            'portalRole' => $isDosen ? 'Dosen Pembimbing' : 'Admin',
            'routePrefix' => $isDosen ? 'dosen.mahasiswa' : 'admin.mahasiswa',
        ]);
    }

    /**
     * Menampilkan detail profil mahasiswa secara individual.
     */
    public function show(Request $request, Peserta $peserta): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $isDosen = $user?->isDosen() ?? false;

        abort_unless($isAdmin || $isDosen, 403);

        $peserta->load(['user', 'prodi']);

        // Pastikan hanya profil mahasiswa yang dapat diakses
        if (! $peserta->user || ! $peserta->user->isMahasiswa()) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        $dosenProdiName = $isDosen ? $user->prodi?->nama_prodi : null;

        if ($isDosen && $peserta->kode_prodi !== $user->kode_prodi && $peserta->program_studi !== $dosenProdiName) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        return Inertia::render('Admin/Mahasiswa/Show', [
            'mahasiswa' => $this->transformPeserta($peserta),
            'canManage' => $isAdmin,
            'portalRole' => $isDosen ? 'Dosen Pembimbing' : 'Admin',
            'routePrefix' => $isDosen ? 'dosen.mahasiswa' : 'admin.mahasiswa',
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
