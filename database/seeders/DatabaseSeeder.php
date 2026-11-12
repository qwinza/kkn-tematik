<?php

namespace Database\Seeders;

use App\Models\MasterKegiatan;
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

        // 4. Master kegiatan contoh untuk Program Studi Sistem Informasi
        $kegiatans = [
            ['kode_kegiatan' => 'SI-01', 'nama_kegiatan' => 'Tahap 1: Analisis Kebutuhan & FGD Warga Iuran Sampah', 'kategori' => 'Kegiatan Lapangan'],
            ['kode_kegiatan' => 'SI-02', 'nama_kegiatan' => 'Tahap 2: Perancangan Arsitektur Database & UI/UX Figma', 'kategori' => 'Perancangan'],
            ['kode_kegiatan' => 'SI-03', 'nama_kegiatan' => 'Tahap 3: Pengkodean Fullstack Web & Mobile SIM Itenas Resik', 'kategori' => 'Pengembangan Sistem'],
            ['kode_kegiatan' => 'SI-04', 'nama_kegiatan' => 'Tahap 4: User Acceptance Test (UAT) Lapangan Bersama RT/RW', 'kategori' => 'Evaluasi & Pengujian'],
            ['kode_kegiatan' => 'SI-05', 'nama_kegiatan' => 'Tahap 5: Deployment Server, Training User & User Manual', 'kategori' => 'Fabrikasi & Implementasi'],
        ];

        foreach ($kegiatans as $kegiatan) {
            MasterKegiatan::updateOrCreate(
                ['kode_kegiatan' => $kegiatan['kode_kegiatan']],
                [
                    'nama_kegiatan' => $kegiatan['nama_kegiatan'],
                    'kategori' => $kegiatan['kategori'],
                    'kode_prodi' => $sistemInformasi->kode_prodi,
                ]
            );
        }
    }
}
