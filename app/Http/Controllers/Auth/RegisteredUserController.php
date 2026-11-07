<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'prodis' => Prodi::query()
                ->orderBy('nama_prodi')
                ->get(['kode_prodi', 'nama_prodi', 'fakultas']),
        ]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nim' => [
                'required',
                'string',
                'max:255',
                'regex:/^[0-9]+$/',
                'unique:users,nim',
                'unique:pesertas,nim',
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use ($validated): void {
            $prodi = Prodi::query()->findOrFail($validated['kode_prodi']);
            $user = User::create([
                'name' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'email' => $validated['email'],
                'role' => 'mahasiswa',
                'password' => Hash::make('password123'),
                'must_change_password' => false,
            ]);

            $user->peserta()->create([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'kode_prodi' => $prodi->kode_prodi,
                'program_studi' => $prodi->nama_prodi,
            ]);
        });

        return redirect()
            ->route('login')
            ->with('success', 'Pendaftaran berhasil. Password sementara Anda adalah password123. Silakan masuk kembali menggunakan NRP, lalu ubah password melalui menu Profil.');
    }
}
