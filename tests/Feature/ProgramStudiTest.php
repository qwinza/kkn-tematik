<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\ProgramStudi;
use App\Models\User;
use Database\Seeders\ProgramStudiSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProgramStudiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan 14 program studi dapat di-seed dengan benar beserta fakultasnya.
     */
    public function test_fourteen_study_programs_can_be_seeded(): void
    {
        $this->seed(ProgramStudiSeeder::class);

        $this->assertDatabaseCount('program_studis', 14);

        $expectedPrograms = [
            // FAD
            'Arsitektur' => 'FAD',
            'Desain Interior' => 'FAD',
            'Desain Produk' => 'FAD',
            'Desain Komunikasi Visual' => 'FAD',

            // FTSP
            'Teknik Sipil' => 'FTSP',
            'Teknik Geodesi' => 'FTSP',
            'Teknik Lingkungan' => 'FTSP',
            'Perencanaan Wilayah dan Kota' => 'FTSP',

            // FTI
            'Teknik Industri' => 'FTI',
            'Teknik Elektro' => 'FTI',
            'Teknik Informatika' => 'FTI',
            'Teknik Mesin' => 'FTI',
            'Sistem Informasi' => 'FTI',
            'Teknik Kimia' => 'FTI',
        ];

        foreach ($expectedPrograms as $namaProdi => $fakultas) {
            $this->assertDatabaseHas('program_studis', [
                'nama_prodi' => $namaProdi,
                'fakultas' => $fakultas,
            ]);
        }
    }

    /**
     * Memastikan nama program studi unik dan seeder aman dijalankan berulang kali (idempotent).
     */
    public function test_program_studi_seeder_is_idempotent_and_names_are_unique(): void
    {
        // Jalankan seeder pertama kali
        $this->seed(ProgramStudiSeeder::class);
        $this->assertDatabaseCount('program_studis', 14);

        // Jalankan seeder kedua kali
        $this->seed(ProgramStudiSeeder::class);
        $this->assertDatabaseCount('program_studis', 14);

        // Memastikan keunikan kolom nama_prodi pada level database
        $this->expectException(QueryException::class);
        ProgramStudi::create([
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);
    }

    /**
     * Memastikan relasi ProgramStudi hasMany Peserta berfungsi.
     */
    public function test_program_studi_has_many_peserta_relationship(): void
    {
        $prodi = ProgramStudi::create([
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);

        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
            'program_studi_id' => $prodi->id,
        ]);

        $this->assertTrue($prodi->pesertas->contains($peserta));
        $this->assertSame(1, $prodi->pesertas->count());
        $this->assertSame($peserta->id, $prodi->pesertas->first()->id);
    }

    /**
     * Memastikan relasi Peserta belongsTo ProgramStudi berfungsi.
     */
    public function test_peserta_belongs_to_program_studi_relationship(): void
    {
        $prodi = ProgramStudi::create([
            'nama_prodi' => 'Teknik Industri',
            'fakultas' => 'FTI',
        ]);

        $user = User::create([
            'name' => 'Budi Santoso',
            'nim' => '112024001',
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Budi Santoso',
            'nim' => '112024001',
            'program_studi' => 'Teknik Industri',
            'program_studi_id' => $prodi->id,
        ]);

        $this->assertNotNull($peserta->programStudi);
        $this->assertSame($prodi->id, $peserta->programStudi->id);
        $this->assertSame('Teknik Industri', $peserta->programStudi->nama_prodi);
        $this->assertSame('FTI', $peserta->programStudi->fakultas);
    }

    /**
     * Memastikan data mahasiswa lama tetap dapat tersimpan tanpa program_studi_id (nullable).
     */
    public function test_old_peserta_can_be_saved_without_program_studi_id(): void
    {
        $user = User::create([
            'name' => 'Citra Dewi',
            'nim' => '212024010',
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Citra Dewi',
            'nim' => '212024010',
            'program_studi' => 'Teknik Sipil',
            'program_studi_id' => null,
        ]);

        $this->assertDatabaseHas('pesertas', [
            'id' => $peserta->id,
            'program_studi' => 'Teknik Sipil',
            'program_studi_id' => null,
        ]);

        $this->assertNull($peserta->programStudi);
    }

    /**
     * Memastikan seeder mencocokkan nilai program_studi lama dan mengisi program_studi_id tanpa menghapus nilai lama.
     */
    public function test_seeder_matches_existing_peserta_program_studi_string(): void
    {
        $user = User::create([
            'name' => 'Dedi Kurniawan',
            'nim' => '152024099',
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Dedi Kurniawan',
            'nim' => '152024099',
            'program_studi' => 'Teknik Informatika',
            'program_studi_id' => null,
        ]);

        $this->assertNull($peserta->program_studi_id);

        // Jalankan seeder
        $this->seed(ProgramStudiSeeder::class);

        $peserta->refresh();

        $this->assertNotNull($peserta->program_studi_id);
        $this->assertSame('Teknik Informatika', $peserta->programStudi->nama_prodi);
        // Pastikan kolom lama program_studi tetap dipertahankan
        $this->assertSame('Teknik Informatika', $peserta->program_studi);
    }
}
