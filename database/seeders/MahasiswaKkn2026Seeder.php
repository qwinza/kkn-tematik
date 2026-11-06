<?php

namespace Database\Seeders;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class MahasiswaKkn2026Seeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('data/mahasiswa_kkn_2026.json');

        if (! File::exists($dataPath)) {
            throw new RuntimeException('File database/data/mahasiswa_kkn_2026.json tidak ditemukan.');
        }

        $rows = json_decode(File::get($dataPath), true, 512, JSON_THROW_ON_ERROR);
        $nameCounts = collect($rows)->countBy(
            fn (array $row) => Str::lower(Str::squish($row['nama_lengkap']))
        );

        $requiredProdiCodes = collect($rows)->pluck('kode_prodi')->unique()->values();
        $existingProdiCodes = Prodi::query()
            ->whereIn('kode_prodi', $requiredProdiCodes)
            ->pluck('kode_prodi');
        $missingProdiCodes = $requiredProdiCodes->diff($existingProdiCodes);

        if ($missingProdiCodes->isNotEmpty()) {
            throw new RuntimeException(
                'Kode prodi belum tersedia di tabel prodi: '.$missingProdiCodes->implode(', ')
            );
        }

        $created = 0;
        $updated = 0;
        $adopted = 0;
        $processed = 0;

        DB::transaction(function () use ($rows,$nameCounts,&$created,&$updated,&$adopted, &$processed): void {
            foreach ($rows as $row) {
                $user = User::query()->where('nim', $row['nim'])->first();
                $matchedByExistingIdentity = false;
                $normalizedName = Str::lower(Str::squish($row['nama_lengkap']));

                // Cari akun lama melalui email yang valid, tanpa pernah menyatukan akun non-mahasiswa.
                if (! $user && $row['email'] !== null) {
                    $user = User::query()
                        ->where('email', $row['email'])
                        ->where('role', 'mahasiswa')
                        ->first();
                    $matchedByExistingIdentity = (bool) $user;
                }

                // Fallback nama hanya untuk nama yang unik di spreadsheet dan di database.
                if (! $user && $nameCounts->get($normalizedName) === 1) {
                    $candidateUsers = Peserta::query()
                        ->with('user')
                        ->whereRaw('LOWER(nama_lengkap) = ?', [Str::lower($row['nama_lengkap'])])
                        ->get()
                        ->pluck('user')
                        ->filter(fn ($candidate) => $candidate && $candidate->isMahasiswa())
                        ->unique('id')
                        ->values();

                    if ($candidateUsers->count() === 1) {
                        $user = $candidateUsers->first();
                        $matchedByExistingIdentity = true;
                    }
                }

                $isNewUser = ! $user;
                $user ??= new User();

                if (! $isNewUser && ! $user->isMahasiswa()) {
                    throw new RuntimeException("ID {$row['nim']} sudah dipakai akun non-mahasiswa.");
                }

                $existingPeserta = $user->exists
                    ? Peserta::query()->where('user_id', $user->id)->first()
                    : null;

                // Jika akun lama memiliki NIM asli, pertahankan agar akun yang sudah berjalan tidak berubah.
                $stableNim = filled($user->nim)
                    ? (string) $user->nim
                    : (filled($existingPeserta?->nim) ? (string) $existingPeserta->nim : $row['nim']);

                $nimTakenByAnotherUser = User::query()
                    ->where('nim', $stableNim)
                    ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
                    ->exists();

                if ($nimTakenByAnotherUser) {
                    throw new RuntimeException("NIM {$stableNim} sudah digunakan oleh akun lain.");
                }

                $participantNimTaken = Peserta::query()
                    ->where('nim', $stableNim)
                    ->when($user->exists, fn ($query) => $query->where('user_id', '!=', $user->id))
                    ->exists();

                if ($participantNimTaken) {
                    throw new RuntimeException("NIM peserta {$stableNim} sudah digunakan oleh data peserta lain.");
                }

                $user->name = $row['nama_lengkap'];
                $user->nim = $stableNim;
                $user->role = 'mahasiswa';

                // Jangan mengganti email akun yang sudah ada. Isi hanya jika kolomnya masih kosong.
                if (blank($user->email) && $row['email'] !== null) {
                    $emailTaken = User::query()
                        ->where('email', $row['email'])
                        ->when($user->exists, fn ($query) => $query->where('id', '!=', $user->id))
                        ->exists();

                    if (! $emailTaken) {
                        $user->email = $row['email'];
                    }
                }

                // Password awal hanya dibuat untuk akun baru. Seeder ulang tidak mereset password.
                if ($isNewUser) {
                    $user->password = Hash::make('password12345');
                    $user->must_change_password = true;
                }

                $user->save();

                // Hanya menulis kolom yang tersedia pada struktur Peserta saat ini.
                Peserta::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama_lengkap' => $row['nama_lengkap'],
                        'nim' => $stableNim,
                        'kode_prodi' => $row['kode_prodi'],
                    ]
                );

                if ($isNewUser) {
                    $created++;
                } else {
                    $updated++;
                    if ($matchedByExistingIdentity) {
                        $adopted++;
                    }
                }
                $processed++;
                if ($processed % 25 === 0 || $processed === count($rows)) {
                    $this->command?->line(
                        "Progress seeder: {$processed}/".count($rows)
                    );
                }
            }
        });

        $this->command?->info("Seeder mahasiswa KKN 2026 selesai. Akun baru: {$created}, akun lama diperbarui/dipakai kembali: {$updated}, diadopsi lewat identitas: {$adopted}.");
        $this->command?->warn('Password awal akun baru: password12345. Akun baru ditandai wajib mengganti password setelah alur penguncian login diintegrasikan.');
    }
}
