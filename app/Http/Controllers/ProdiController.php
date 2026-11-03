<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProdiRequest;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProdiController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $filters = $request->validate([
            'fakultas' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['kode_prodi', 'nama_prodi', 'fakultas', 'pesertas_count', 'master_kegiatans_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $sort = $filters['sort'] ?? 'nama_prodi';
        $direction = $filters['direction'] ?? 'asc';

        return Inertia::render('Prodi/Index', [
            'prodis' => Prodi::query()
                ->withCount(['pesertas', 'masterKegiatans'])
                ->when(($filters['fakultas'] ?? '') === '__empty', fn ($query) => $query->where('fakultas', ''))
                ->when(! in_array($filters['fakultas'] ?? '', ['', '__empty'], true), fn ($query) => $query->where('fakultas', $filters['fakultas']))
                ->orderBy($sort, $direction)
                ->get(),
            'fakultasOptions' => Prodi::query()->distinct()->orderBy('fakultas')->pluck('fakultas'),
            'filters' => [
                'fakultas' => $filters['fakultas'] ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function store(ProdiRequest $request): RedirectResponse
    {
        Prodi::create($request->validated());

        return redirect()->back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function update(ProdiRequest $request, Prodi $prodi): RedirectResponse
    {
        $prodi->update($request->validated());

        return redirect()->back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function destroy(Request $request, Prodi $prodi): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($prodi->pesertas()->exists() || $prodi->masterKegiatans()->exists()) {
            return redirect()->back()->with('error', 'Program studi tidak dapat dihapus karena masih digunakan.');
        }

        $prodi->delete();

        return redirect()->back()->with('success', 'Program studi berhasil dihapus.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Halaman program studi hanya dapat diakses oleh Admin.');
        }
    }
}
