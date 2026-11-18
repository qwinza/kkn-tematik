<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'filters' => [
                'search' => $filters['search'] ?? '',
                'kode_prodi' => $filters['kode_prodi'] ?? '',
            ],
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

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->isDpp(), 404);

        $user->delete();

        return back()->with('success', 'Akun DPP berhasil dihapus.');
    }
}
