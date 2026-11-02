<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterKegiatanRequest;
use App\Models\MasterKegiatan;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $search = (string) $request->input('search', '');

        $kegiatans = MasterKegiatan::query()
            ->with('prodi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_kegiatan', 'like', "%{$search}%")
                        ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                        ->orWhere('kategori', 'like', "%{$search}%");
                });
            })
            ->withCount('logKegiatans')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('MasterKegiatan/Index', [
            'kegiatans' => $kegiatans,
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi']),
            'filters' => [
                'search' => $search,
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
     * Hapus data master kegiatan.
     */
    public function destroy(Request $request, MasterKegiatan $masterKegiatan): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Hanya admin yang berhak menghapus master kegiatan.');
        }

        if ($masterKegiatan->logKegiatans()->exists()) {
            return redirect()->back()->with('error', 'Master kegiatan tidak dapat dihapus karena sudah memiliki relasi log kegiatan.');
        }

        $masterKegiatan->delete();

        return redirect()->back()->with('success', 'Master kegiatan berhasil dihapus.');
    }
}
