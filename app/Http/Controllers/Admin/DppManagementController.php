<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
=======
use App\Models\DppAccessRequest;
>>>>>>> origin/devs
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
<<<<<<< HEAD
use Illuminate\Support\Facades\Hash;
=======
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
>>>>>>> origin/devs
use Inertia\Inertia;
use Inertia\Response;

class DppManagementController extends Controller
{
<<<<<<< HEAD
    public function index(Request $request): Response
    {
=======

    /**
     * Menampilkan daftar DPP dan permintaan persetujuan dosen.
     */
    public function index(Request $request): Response
    {

>>>>>>> origin/devs
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
        ]);

<<<<<<< HEAD
        $dpps = User::query()
            ->where('role', 'dpp')
            ->with('prodi')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($subquery) use ($search): void {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('prodi', fn ($prodiQuery) => $prodiQuery->where('nama_prodi', 'like', "%{$search}%"));
                });
            })
            ->when($filters['kode_prodi'] ?? null, fn ($query, string $kodeProdi) => $query->where('kode_prodi', $kodeProdi))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'kode_prodi' => $user->kode_prodi,
                'prodi' => $user->prodi?->nama_prodi,
                'fakultas' => $user->prodi?->fakultas,
                'must_change_password' => $user->must_change_password,
            ]);

        return Inertia::render('Admin/DPP/Index', [
            'dpps' => $dpps,
            'prodis' => Prodi::query()->orderBy('nama_prodi')->get(['kode_prodi', 'nama_prodi', 'fakultas']),
=======

        $query = User::query()
            ->where('role', 'dpp')
            ->with('dppProfile.prodi')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('dppProfile.prodi', function ($prodiQuery) use ($search) {
                            $prodiQuery->where('nama_prodi', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['kode_prodi'] ?? null, function ($query, $kodeProdi) {
                $query->whereHas('dppProfile', function ($profileQuery) use ($kodeProdi) {
                    $profileQuery->where('kode_prodi', $kodeProdi);
                });
            })
            ->orderBy('name');

        $dpps = $query->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'kode_prodi' => $user->dppProfile?->kode_prodi,
                'prodi' => $user->dppProfile?->prodi?->nama_prodi,
                'fakultas' => $user->dppProfile?->prodi?->fakultas,
                'must_change_password' => $user->must_change_password,
            ]);


        $pendingRequests = DppAccessRequest::query()
            ->where(
                'approval_status',
                DppAccessRequest::STATUS_MENUNGGU
            )
            ->latest()
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString()
            ->through(fn (DppAccessRequest $item) => [
                'id' => $item->id,
                'username_sikad' => $item->username_sikad,
                'nomor_dosen' => $item->nomor_dosen,
                'nama' => $item->nama,
                'nidn' => $item->nidn,
                'gelar_depan' => $item->gelar_depan,
                'gelar_belakang' => $item->gelar_belakang,
                'kode_fakultas_sikad' => $item->kode_fakultas_sikad,
                'kode_prodi_sikad' => $item->kode_prodi_sikad,
                'status_sikad' => $item->status_sikad,
                'created_at' => $item->created_at?->format('d-m-Y H:i'),
            ]);


        $pendingCount = DppAccessRequest::where(
            'approval_status',
            DppAccessRequest::STATUS_MENUNGGU
        )->count();


        return Inertia::render('Admin/DPP/Index', [
            'dpps' => $dpps,
            'pendingRequests' => $pendingRequests,
            'pendingCount' => $pendingCount,
            'prodis' => Prodi::query()
                ->orderBy('nama_prodi')
                ->get(['kode_prodi', 'nama_prodi', 'fakultas']),
>>>>>>> origin/devs
            'filters' => [
                'search' => $filters['search'] ?? '',
                'kode_prodi' => $filters['kode_prodi'] ?? '',
            ],
<<<<<<< HEAD
            'totalCount' => User::query()->where('role', 'dpp')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nim' => null,
            'role' => 'dpp',
            'kode_prodi' => $validated['kode_prodi'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Akun DPP berhasil dibuat.');
    }

=======
            'totalCount' => User::where('role', 'dpp')->count(),
        ]);
    }

    /**
     * Menyetujui permintaan dosen dan membuat akun DPP.
     */
    public function approveAccessRequest(
        Request $request,
        DppAccessRequest $accessRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use (
            $request,
            $accessRequest,
            $validated
        ): void {
            $accessRequest = DppAccessRequest::query()
                ->lockForUpdate()
                ->findOrFail($accessRequest->id);

            abort_unless($accessRequest->isPending(), 409);

            // Cari akun DPP lama berdasarkan username SIKAD yang cocok dengan email.
            $user = User::query()
                ->where('email', $accessRequest->username_sikad)
                ->lockForUpdate()
                ->first();

            if ($user) {
                // Jangan menghubungkan permintaan DPP ke akun dengan role lain.
                abort_unless(
                    $user->isDpp(),
                    409,
                    'Username SIKAD sudah terhubung ke akun non-DPP.'
                );

                // Satu akun hanya boleh terhubung ke satu permintaan SIKAD.
                $linkedElsewhere = DppAccessRequest::query()
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $accessRequest->id)
                    ->exists();

                abort_unless(
                    ! $linkedElsewhere,
                    409,
                    'Akun DPP sudah terhubung ke permintaan lain.'
                );

                $user->update([
                    'name' => $accessRequest->nama,
                ]);

                $user->dppProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['kode_prodi' => $validated['kode_prodi']]
                );
            } else {
                // Buat akun baru hanya jika belum ada akun DPP yang cocok.
                $user = User::create([
                    'name' => $accessRequest->nama,
                    'email' => null,
                    'nim' => null,
                    'role' => 'dpp',
                    'password' => Hash::make(Str::random(64)),
                    'must_change_password' => false,
                ]);

                $user->dppProfile()->create([
                    'kode_prodi' => $validated['kode_prodi'],
                ]);
            }

            $accessRequest->update([
                'approval_status' => DppAccessRequest::STATUS_DISETUJUI,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'user_id' => $user->id,
            ]);
        });

        return back()->with(
            'success',
            'Permintaan akun dosen berhasil disetujui. Akun DPP sudah aktif.'
        );
    }

    /**
     * Menolak permintaan dosen.
     */
    public function rejectAccessRequest(
        Request $request,
        DppAccessRequest $accessRequest
    ): RedirectResponse {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use (
            $request,
            $accessRequest,
            $validated
        ): void {
            $accessRequest = DppAccessRequest::query()
                ->lockForUpdate()
                ->findOrFail($accessRequest->id);

            abort_unless($accessRequest->isPending(), 409);

            $accessRequest->update([
                'approval_status' => DppAccessRequest::STATUS_DITOLAK,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);
        });

        return back()->with(
            'success',
            'Permintaan akun dosen berhasil ditolak.'
        );
    }

    /**
     * Memperbarui nama dan program studi akun DPP.
     */
>>>>>>> origin/devs
    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
<<<<<<< HEAD
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Data akun DPP berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
        ])->save();

        return back()->with('success', 'Password akun DPP berhasil direset.');
    }

=======
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use ($validated, $user): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
            ]);

            $user->dppProfile()->updateOrCreate(
                ['user_id' => $user->id],
                ['kode_prodi' => $validated['kode_prodi']]
            );
        });

        return back()->with(
            'success',
            'Data akun DPP berhasil diperbarui.'
        );
    }

    /**
     * Menghapus akun DPP.
     */
>>>>>>> origin/devs
    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

<<<<<<< HEAD
        $user->delete();

        return back()->with('success', 'Akun DPP berhasil dihapus.');
=======
        DB::transaction(function () use ($user): void {
            $user->delete();
        });

        return back()->with(
            'success',
            'Akun DPP berhasil dihapus.'
        );
>>>>>>> origin/devs
    }
}
