<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstLoginPasswordController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isMahasiswa() || $user->isDpp()),
            403
        );

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/FirstLoginPassword');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isMahasiswa() || $user->isDpp()),
            403
        );

        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                Password::defaults(),
                'confirmed',
                'different:current_password',
            ],
        ], [
            'password.different' => 'Password baru harus berbeda dari password sementara.',
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Password berhasil diganti. Selamat menggunakan aplikasi KKN Tematik.');
    }
}