<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class DppManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kode_prodi' => ['nullable', 'string', 'exists:prodi,kode_prodi'],
        ]);

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

        return Inertia::render('Admin/DPP/Index', [
            'dpps' => $dpps,
            'prodis' => Prodi::query()
                ->orderBy('nama_prodi')
                ->get(['kode_prodi', 'nama_prodi', 'fakultas']),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'kode_prodi' => $filters['kode_prodi'] ?? '',
            ],
            'totalCount' => User::where('role', 'dpp')->count(),
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

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'nim' => null,
                'role' => 'dpp',
                'password' => Hash::make($validated['password']),
                'must_change_password' => true,
            ]);

            $user->dppProfile()->create([
                'kode_prodi' => $validated['kode_prodi'],
            ]);
        });

        return back()->with(
            'success',
            'Akun DPP berhasil dibuat. Pengguna wajib mengganti password saat login pertama.'
        );
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use ($validated, $user): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $user->dppProfile()->updateOrCreate(
                ['user_id' => $user->id],
                ['kode_prodi' => $validated['kode_prodi']]
            );
        });

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

        return back()->with(
            'success',
            'Password DPP berhasil direset. Pengguna wajib mengganti password saat login berikutnya.'
        );
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $user->delete();

        return back()->with('success', 'Akun DPP berhasil dihapus.');
    }
}