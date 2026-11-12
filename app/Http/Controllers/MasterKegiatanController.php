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
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $isDpp = $user?->isDpp() ?? false;

        abort_unless($isAdmin || $isDpp, 403);

        $dppKodeProdi = $isDpp
            ? $user->dppProfile?->kode_prodi
            : null;

        abort_if($isDpp && ! $dppKodeProdi, 403);

        $rules = [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => [
                'nullable',
                Rule::in([
                    'id',
                    'kode_kegiatan',
                    'nama_kegiatan',
                    'kategori',
                    'prodi',
                    'log_kegiatans_count',
                ]),
            ],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];

        if ($isAdmin) {
            $rules['kode_prodi'] = [
                'nullable',
                'string',
                'exists:prodi,kode_prodi',
            ];
        }

        $filters = $request->validate($rules);

        $search = $filters['search'] ?? '';
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';

        $kodeProdiFilter = $isDpp
            ? $dppKodeProdi
            : ($filters['kode_prodi'] ?? null);

        $query = MasterKegiatan::query()
            ->with('prodi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_kegiatan', 'like', "%{$search}%")
                        ->orWhere('nama_kegiatan', 'like', "%{$search}%")
                        ->orWhere('kategori', 'like', "%{$search}%")
                        ->orWhereHas('prodi', function ($prodiQuery) use ($search) {
                            $prodiQuery->where('nama_prodi', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $kodeProdiFilter,
                fn ($query) => $query->where('kode_prodi', $kodeProdiFilter)
            )
            ->withCount('logKegiatans');

        if ($sort === 'prodi') {
            $query->orderBy(
                Prodi::query()
                    ->select('nama_prodi')
                    ->whereColumn(
                        'prodi.kode_prodi',
                        'master_kegiatans.kode_prodi'
                    ),
                $direction
            );
        } elseif ($sort !== 'id') {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('id');
        }

        $kegiatans = $query->paginate(10)->withQueryString();

        $prodis = $isAdmin
            ? Prodi::query()->orderBy('nama_prodi')
                ->get(['kode_prodi', 'nama_prodi'])
            : Prodi::query()->where('kode_prodi', $dppKodeProdi)
                ->get(['kode_prodi', 'nama_prodi']);

        return Inertia::render('MasterKegiatan/Index', [
            'kegiatans' => $kegiatans,
            'prodis' => $prodis,
            'filters' => [
                'search' => $search,
                'kode_prodi' => $kodeProdiFilter ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
            'canManage' => true,
            'isAdmin' => $isAdmin,
            'isDpp' => $isDpp,
        ]);
    }

    public function store(MasterKegiatanRequest $request): RedirectResponse
    {
        MasterKegiatan::create($request->validated());

        return back()->with('success', 'Master kegiatan berhasil ditambahkan.');
    }

    public function update(
        MasterKegiatanRequest $request,
        MasterKegiatan $masterKegiatan
    ): RedirectResponse {
        $masterKegiatan->update($request->validated());

        return back()->with('success', 'Master kegiatan berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        MasterKegiatan $masterKegiatan
    ): RedirectResponse {
        $user = $request->user();

        if ($user?->isDpp()) {
            $dppKodeProdi = $user->dppProfile?->kode_prodi;

            abort_unless(
                $dppKodeProdi
                && $masterKegiatan->kode_prodi === $dppKodeProdi,
                403
            );
        } else {
            abort_unless($user?->isAdmin(), 403);
        }

        if ($masterKegiatan->logKegiatans()->exists()) {
            return back()->with(
                'error',
                'Master kegiatan tidak dapat dihapus karena sudah memiliki relasi log kegiatan.'
            );
        }

        $masterKegiatan->delete();

        return back()->with('success', 'Master kegiatan berhasil dihapus.');
    }
}