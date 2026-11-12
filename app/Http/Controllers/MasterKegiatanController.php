<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterKegiatanRequest;
use App\Models\MasterKegiatan;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MasterKegiatanController extends Controller
{
    /**
     * Tampilkan daftar master kegiatan.
     */
    public function index(Request $request): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Akses ditolak. Halaman Master Kegiatan hanya dapat diakses oleh Admin.');
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
            'sort' => ['nullable', Rule::in(['id', 'kode_kegiatan', 'nama_kegiatan', 'kategori', 'prodi', 'log_kegiatans_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = $filters['search'] ?? '';
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        $query = MasterKegiatan::query()
            ->with('prodi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_kegiatan', 'like', "%{$search}%")
                        ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                        ->orWhere('kategori', 'like', "%{$search}%")
                        ->orWhereHas('prodi', fn ($prodiQuery) => $prodiQuery->where('nama_prodi', 'like', "%{$search}%"));
                });
            })
            ->when($filters['kode_prodi'] ?? null, fn ($query, $kodeProdi) => $query->where('kode_prodi', $kodeProdi))
            ->withCount('logKegiatans');

        if ($sort === 'prodi') {
            $query->orderBy(Prodi::query()
                ->select('nama_prodi')
                ->whereColumn('prodi.kode_prodi', 'master_kegiatans.kode_prodi'), $direction);
        } elseif ($sort !== 'id') {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('id');
        }

        $kegiatans = $query->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterKegiatan/Index', [
            'kegiatans' => $kegiatans,
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi']),
            'filters' => [
                'search' => $search,
                'kode_prodi' => $filters['kode_prodi'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
            'canManage' => $request->user()?->isAdmin() ?? false,
        ]);
    }

    /**
     * Simpan master kegiatan baru.
     */
    public function store(MasterKegiatanRequest $request): RedirectResponse
    {
        MasterKegiatan::create($request->validated());

        return redirect()->back()->with('success', 'Master kegiatan berhasil ditambahkan.');
    }

    /**
     * Perbarui data master kegiatan yang sudah ada.
     */
    public function update(MasterKegiatanRequest $request, MasterKegiatan $masterKegiatan): RedirectResponse
    {
        $masterKegiatan->update($request->validated());

        return redirect()->back()->with('success', 'Master kegiatan berhasil diperbarui.');
    }

    /**
     * Ubah status aktif master kegiatan tanpa menghapus riwayatnya.
     */
    public function updateStatus(Request $request, MasterKegiatan $masterKegiatan): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Hanya admin yang berhak mengubah status jadwal kegiatan.');
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $masterKegiatan->update(['is_active' => $validated['is_active']]);
        $status = $masterKegiatan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()->with('success', "Jadwal kegiatan berhasil {$status}.");
    }
}
