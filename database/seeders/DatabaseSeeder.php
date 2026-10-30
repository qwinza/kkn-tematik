<?php

namespace Database\Seeders;

use App\Models\MasterKegiatan;
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

        // 2. Akun Mahasiswa Dummy & Relasi Peserta
        $mahasiswa = User::updateOrCreate(
            ['nim' => '152024038'],
            [
                'name' => 'Aa Faris Ahmad Shidiq',
                'email' => null,
                'password' => Hash::make('password123'),
                'role' => 'mahasiswa',
            ]
        );

        Peserta::updateOrCreate(
            ['user_id' => $mahasiswa->id],
            [
                'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
                'nim' => '152024038',
                'program_studi' => 'Teknik Informatika',
            ]
        );

        // Inisialisasi Master Kegiatan dasar
        $masterKegiatans = [
            [
                'kode_kegiatan' => 'KKN-001',
                'nama_kegiatan' => 'Sosialisasi Program Kerja KKN Tematik',
                'kategori' => 'Sosialisasi',
            ],
            [
                'kode_kegiatan' => 'KKN-002',
                'nama_kegiatan' => 'Pelatihan Teknologi dan Digitalisasi UMKM',
                'kategori' => 'Pelatihan',
            ],
            [
                'kode_kegiatan' => 'KKN-003',
                'nama_kegiatan' => 'Pembersihan dan Penataan Lingkungan Desa',
                'kategori' => 'Lingkungan',
            ],
        ];

        foreach ($masterKegiatans as $kegiatan) {
            MasterKegiatan::firstOrCreate(
                ['kode_kegiatan' => $kegiatan['kode_kegiatan']],
                $kegiatan
            );
        }
    }
}
