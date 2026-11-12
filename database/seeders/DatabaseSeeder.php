<?php

namespace Database\Seeders;

use App\Models\Peserta;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Panggil KegiatanSeeder untuk mengisi 14 Prodi dan Master Kegiatannya
        $this->call(KegiatanSeeder::class);

        // 2. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@itenas.ac.id'],
            [
                'name' => 'Admin KKN ITENAS',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'nim' => null,
            ]
        );

        // 3. Akun Mahasiswa Dummy 1 (Aa Faris - Informatika)
        $mahasiswa1 = User::updateOrCreate(
            ['nim' => '152024038'],
            [
                'name' => 'Aa Faris Ahmad Shidiq',
                'email' => null,
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
            ]
        );

        Peserta::updateOrCreate(
            ['user_id' => $mahasiswa1->id],
            [
                'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
                'nim' => '152024038',
                'kode_prodi' => 'IF',
            ]
        );

        // 4. Akun Mahasiswa Dummy 2 (Nadia Amalia - DKV)
        $mahasiswa2 = User::updateOrCreate(
            ['nim' => '152022041'],
            [
                'name' => 'Nadia Amalia',
                'email' => 'nadia.amalia@itenas.ac.id',
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
            ]
        );

        Peserta::updateOrCreate(
            ['user_id' => $mahasiswa2->id],
            [
                'nama_lengkap' => 'Nadia Amalia',
                'nim' => '152022041',
                'kode_prodi' => 'DKV',
            ]
        );

        // 5. Akun Mahasiswa Dummy 3 (Farid Annas - Sistem Informasi)
        $farid = User::updateOrCreate(
            ['nim' => '152025083'],
            [
                'name' => 'Farid Annas',
                'email' => null,
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
            ]
        );

        Peserta::updateOrCreate(
            ['user_id' => $farid->id],
            [
                'nama_lengkap' => 'Farid Annas',
                'nim' => '152025083',
                'program_studi' => 'Sistem Informasi',
                'kode_prodi' => 'SI',
            ]
        );
    }
}