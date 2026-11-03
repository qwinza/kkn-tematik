<?php

namespace Database\Seeders;

use App\Models\Peserta;
use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programStudis = [
            // Fakultas Arsitektur dan Desain (FAD)
            ['nama_prodi' => 'Arsitektur', 'fakultas' => 'FAD'],
            ['nama_prodi' => 'Desain Interior', 'fakultas' => 'FAD'],
            ['nama_prodi' => 'Desain Produk', 'fakultas' => 'FAD'],
            ['nama_prodi' => 'Desain Komunikasi Visual', 'fakultas' => 'FAD'],

            // Fakultas Teknik Sipil dan Perencanaan (FTSP)
            ['nama_prodi' => 'Teknik Sipil', 'fakultas' => 'FTSP'],
            ['nama_prodi' => 'Teknik Geodesi', 'fakultas' => 'FTSP'],
            ['nama_prodi' => 'Teknik Lingkungan', 'fakultas' => 'FTSP'],
            ['nama_prodi' => 'Perencanaan Wilayah dan Kota', 'fakultas' => 'FTSP'],

            // Fakultas Teknologi Industri (FTI)
            ['nama_prodi' => 'Teknik Industri', 'fakultas' => 'FTI'],
            ['nama_prodi' => 'Teknik Elektro', 'fakultas' => 'FTI'],
            ['nama_prodi' => 'Teknik Informatika', 'fakultas' => 'FTI'],
            ['nama_prodi' => 'Teknik Mesin', 'fakultas' => 'FTI'],
            ['nama_prodi' => 'Sistem Informasi', 'fakultas' => 'FTI'],
            ['nama_prodi' => 'Teknik Kimia', 'fakultas' => 'FTI'],
        ];

        foreach ($programStudis as $prodi) {
            ProgramStudi::updateOrCreate(
                ['nama_prodi' => $prodi['nama_prodi']],
                ['fakultas' => $prodi['fakultas']]
            );
        }

        // Lakukan pencocokan nilai program_studi lama pada tabel pesertas dan isi program_studi_id
        $prodiMap = ProgramStudi::all()->keyBy('nama_prodi');

        Peserta::whereNotNull('program_studi')
            ->whereNull('program_studi_id')
            ->each(function (Peserta $peserta) use ($prodiMap) {
                $prodiName = trim((string) $peserta->program_studi);
                if ($prodiMap->has($prodiName)) {
                    $peserta->update([
                        'program_studi_id' => $prodiMap->get($prodiName)->id,
                    ]);
                }
            });
    }
}
