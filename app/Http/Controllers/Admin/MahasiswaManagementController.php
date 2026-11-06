<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MahasiswaManagementController extends Controller
{
    private const DEFAULT_PASSWORD = 'password12345';

    private function getMahasiswa(Peserta $peserta): User
    {
        $user = $peserta->user;

        abort_unless($user && $user->isMahasiswa(), 404);

        return $user;
    }

    /**
     * Tambah mahasiswa dan akun login.
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
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use ($validated): void {
            $prodi = Prodi::findOrFail($validated['kode_prodi']);

            $user = User::create([
                'name' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'email' => $validated['email'] ?: null,
                'role' => 'mahasiswa',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'must_change_password' => true,
            ]);

            $user->peserta()->create([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'kode_prodi' => $prodi->kode_prodi,
                'program_studi' => $prodi->nama_prodi,
            ]);
        });

        return back()->with(
            'success',
            'Mahasiswa berhasil ditambahkan. Password awal: '.self::DEFAULT_PASSWORD
        );
    }

    /**
     * Perbarui identitas mahasiswa.
     */
    public function update(Request $request, Peserta $peserta): RedirectResponse
    {
        $user = $this->getMahasiswa($peserta);

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nim' => [
                'required',
                'string',
                'max:255',
                'regex:/^[0-9]+$/',
                Rule::unique('users', 'nim')->ignore($user->id),
                Rule::unique('pesertas', 'nim')->ignore($peserta->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'kode_prodi' => ['required', 'exists:prodi,kode_prodi'],
        ]);

        DB::transaction(function () use ($validated, $peserta, $user): void {
            $prodi = Prodi::findOrFail($validated['kode_prodi']);

            $user->update([
                'name' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'email' => $validated['email'] ?: null,
            ]);

            $peserta->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'nim' => $validated['nim'],
                'kode_prodi' => $prodi->kode_prodi,
                'program_studi' => $prodi->nama_prodi,
            ]);
        });

        return back()->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    /**
     * Reset password mahasiswa.
     */
    public function resetPassword(Peserta $peserta): RedirectResponse
    {
        $user = $this->getMahasiswa($peserta);

        $user->update([
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'must_change_password' => true,
        ]);

        return back()->with(
            'success',
            'Password berhasil direset ke password awal. Mahasiswa wajib menggantinya saat login.'
        );
    }

    /**
     * Hapus mahasiswa hanya jika belum memiliki riwayat.
     */
    public function destroy(Peserta $peserta): RedirectResponse
    {
        $user = $this->getMahasiswa($peserta);

        if (
            $peserta->logKegiatans()->exists()
            || $peserta->rencanaKehadirans()->exists()
        ) {
            return back()->with(
                'error',
                'Mahasiswa tidak dapat dihapus karena sudah memiliki riwayat kegiatan atau rencana kehadiran.'
            );
        }

        DB::transaction(function () use ($user): void {
            $user->delete();
        });

        return back()->with('success', 'Data mahasiswa berhasil dihapus.');
    }
}
