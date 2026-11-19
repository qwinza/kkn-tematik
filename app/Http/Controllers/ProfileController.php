<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $mahasiswaProfile = null;

        if ($user->isMahasiswa()) {
            $peserta = $user->peserta()
                ->with(['prodi', 'logKegiatans' => fn ($query) => $query->where('status', 'disetujui')])
                ->first();

            if ($peserta) {
                $mahasiswaProfile = [
                    'nama_lengkap' => $peserta->nama_lengkap,
                    'nim' => $peserta->nim,
                    'program_studi' => $peserta->prodi?->nama_prodi ?? $peserta->program_studi,
                    'fakultas' => $peserta->prodi?->fakultas,
                    'angkatan' => $peserta->angkatan,
                    'no_telepon' => $peserta->no_telepon,
                    'total_jam_disetujui' => round($peserta->logKegiatans->sum(fn ($log) => $log->durasiJam()), 1),
                ];
            }
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'isDpp' => $user->isDpp(),
            'canManageLocalPassword' => ! $user->isDpp() && ! $user->isMahasiswa(),
            'canDeleteAccount' => ! $user->isDpp() && ! $user->isMahasiswa(),
            'mahasiswaProfile' => $mahasiswaProfile,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(
            $user->isDpp(),
            403,
            'Akun DPP dikelola oleh Admin dan menggunakan autentikasi SIKAD.'
        );

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
