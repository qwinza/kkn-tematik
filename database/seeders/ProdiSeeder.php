<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdiSeeder extends Seeder
{
    /**
     * Seed the program studies used by participants and activities.
     */
    public function run(): void
    {
        $prodis = [
            ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Industri', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'TK', 'nama_prodi' => 'Teknik Kimia', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'MS', 'nama_prodi' => 'Teknik Mesin', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'EL', 'nama_prodi' => 'Teknik Elektro', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'IF', 'nama_prodi' => 'Informatika', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'SI-01', 'nama_prodi' => 'Sistem Informasi', 'fakultas' => 'FTI'],
            ['kode_prodi' => 'TG', 'nama_prodi' => 'Teknik Geodesi', 'fakultas' => 'FTSP'],
            ['kode_prodi' => 'TL', 'nama_prodi' => 'Teknik Lingkungan', 'fakultas' => 'FTSP'],
            ['kode_prodi' => 'TS', 'nama_prodi' => 'Teknik Sipil', 'fakultas' => 'FTSP'],
            ['kode_prodi' => 'PWK', 'nama_prodi' => 'Perencanaan Wilayah dan Kota', 'fakultas' => 'FTSP'],
            ['kode_prodi' => 'ARS', 'nama_prodi' => 'Arsitektur', 'fakultas' => 'FAD'],
            ['kode_prodi' => 'DKV', 'nama_prodi' => 'Desain Komunikasi Visual', 'fakultas' => 'FAD'],
            ['kode_prodi' => 'DI', 'nama_prodi' => 'Desain Interior', 'fakultas' => 'FAD'],
            ['kode_prodi' => 'DP', 'nama_prodi' => 'Desain Produk', 'fakultas' => 'FAD'],
        ];

        foreach ($prodis as $prodi) {
            DB::table('prodi')->updateOrInsert(
                ['kode_prodi' => $prodi['kode_prodi']],
                [
                    'nama_prodi' => $prodi['nama_prodi'],
                    'fakultas' => $prodi['fakultas'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
