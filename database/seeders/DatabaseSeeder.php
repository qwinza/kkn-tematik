<?php

namespace Database\Seeders;

use App\Models\Peserta;
use App\Models\Prodi;
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
        // 1. Akun Admin
        User::updateOrCreate(
            ['email' => 'admin@itenas.ac.id'],
            [
                'name' => 'Admin KKN ITENAS',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'nim' => null,
            ]
        );

        // 2. Akun Mahasiswa Dummy 1 & Relasi Peserta
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
            ]
        );

        // 3. Akun Mahasiswa Dummy 2 (Nadia Amalia)
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
            ]
        );

        $sistemInformasi = Prodi::updateOrCreate(
            ['nama_prodi' => 'Sistem Informasi'],
            [
                'kode_prodi' => 'SI-01',
                'fakultas' => 'FTI',
            ]
        );

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
                'kode_prodi' => $sistemInformasi->kode_prodi,
            ]
        );
    }
}
