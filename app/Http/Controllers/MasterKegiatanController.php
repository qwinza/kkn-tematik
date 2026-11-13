<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterKegiatanRequest;
use App\Models\MasterKegiatan;
use App\Models\Prodi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $kodeBerikutnyaPerProdi = $this->getNextCodesPerProdi($prodis);

        return Inertia::render('MasterKegiatan/Index', [
            'kegiatans' => $kegiatans,
            'prodis' => $prodis,
            'kodeBerikutnyaPerProdi' => $kodeBerikutnyaPerProdi,
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
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            Prodi::query()
                ->where('kode_prodi', $validated['kode_prodi'])
                ->lockForUpdate()
                ->firstOrFail();

            $validated['kode_kegiatan'] = $this->getNextCodeForProdi($validated['kode_prodi']);

            MasterKegiatan::create($validated);
        });

        return back()->with('success', 'Master kegiatan berhasil ditambahkan.');
    }

    public function update(
        MasterKegiatanRequest $request,
        MasterKegiatan $masterKegiatan
    ): RedirectResponse {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $masterKegiatan): void {
            if ($validated['kode_prodi'] !== $masterKegiatan->kode_prodi) {
                Prodi::query()
                    ->where('kode_prodi', $validated['kode_prodi'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $masterKegiatan->kode_kegiatan = $this->getNextCodeForProdi($validated['kode_prodi']);
            }

            $masterKegiatan->fill($validated)->save();
        });

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

    /**
     * @param  iterable<Prodi>  $prodis
     * @return array<string, string>
     */
    private function getNextCodesPerProdi(iterable $prodis): array
    {
        $codesByProdi = MasterKegiatan::query()
            ->get(['kode_prodi', 'kode_kegiatan'])
            ->groupBy('kode_prodi');

        $nextCodes = [];

        foreach ($prodis as $prodi) {
            $highestNumber = 0;

            foreach ($codesByProdi->get($prodi->kode_prodi, collect()) as $kegiatan) {
                $highestNumber = max(
                    $highestNumber,
                    $this->getSequenceNumber($kegiatan->kode_kegiatan, $prodi->kode_prodi)
                );
            }

            $nextCodes[$prodi->kode_prodi] = $this->formatCode($prodi->kode_prodi, $highestNumber + 1);
        }

        return $nextCodes;
    }

    private function getNextCodeForProdi(string $kodeProdi): string
    {
        $kegiatans = MasterKegiatan::query()
            ->where('kode_prodi', $kodeProdi)
            ->get(['kode_kegiatan']);

        $highestNumber = 0;

        foreach ($kegiatans as $kegiatan) {
            $highestNumber = max(
                $highestNumber,
                $this->getSequenceNumber($kegiatan->kode_kegiatan, $kodeProdi)
            );
        }

        return $this->formatCode($kodeProdi, $highestNumber + 1);
    }

    private function getSequenceNumber(string $kodeKegiatan, string $kodeProdi): int
    {
        $prefix = preg_quote($this->getActivityCodePrefix($kodeProdi), '/');

        return preg_match('/^'.$prefix.'(\d+)$/i', $kodeKegiatan, $matches) === 1
            ? (int) $matches[1]
            : 0;
    }

    private function formatCode(string $kodeProdi, int $number): string
    {
        $activityPrefix = $this->getActivityCodePrefix($kodeProdi);

        return $activityPrefix === 'IF-WP'
            ? $activityPrefix.$number
            : $activityPrefix.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    private function getActivityCodePrefix(string $kodeProdi): string
    {
        $prodiPrefix = preg_match('/^([A-Za-z]+)-\d+$/', $kodeProdi, $matches) === 1
            ? strtoupper($matches[1])
            : strtoupper($kodeProdi);

        return $prodiPrefix === 'IF'
            ? 'IF-WP'
            : $prodiPrefix.'-';
    }
}
