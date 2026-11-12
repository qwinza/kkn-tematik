<?php

namespace App\Http\Middleware;

use App\Models\LogKegiatan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    ...$request->user()->toArray(),
                    'is_admin' => $request->user()->isAdmin(),
                    'is_mahasiswa' => $request->user()->isMahasiswa(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'activityNotifications' => fn () => $request->user()?->isMahasiswa() && $request->user()->peserta
                ? LogKegiatan::query()
                    ->where('peserta_id', $request->user()->peserta->id)
                    ->whereIn('status', ['menunggu', 'disetujui', 'ditolak'])
                    ->with('masterKegiatan:id,nama_kegiatan')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(['id', 'master_kegiatan_id', 'status', 'updated_at'])
                    ->map(fn (LogKegiatan $log): array => [
                        'id' => $log->id,
                        'route_key' => $log->getRouteKey(),
                        'status' => $log->status,
                        'kegiatan' => $log->masterKegiatan?->nama_kegiatan ?? 'Log kegiatan',
                        'updated_at' => $log->updated_at?->toDateTimeString(),
                    ])
                : [],
        ];
    }
}
