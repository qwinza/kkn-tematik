<?php

namespace Tests\Feature;

use Database\Seeders\KegiatanSeeder;
use Database\Seeders\MahasiswaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_seeders_load_the_activity_catalog_and_database_seeder_loads_testing_accounts(): void
    {
        $this->seed(KegiatanSeeder::class);
        $this->seed(MahasiswaSeeder::class);
        $this->seed();

        $this->assertDatabaseHas('prodi', [
            'kode_prodi' => 'SI-01',
            'nama_prodi' => 'Sistem Informasi',
        ]);
        $this->assertDatabaseHas('master_kegiatans', [
            'kode_kegiatan' => 'SI-01',
            'kode_prodi' => 'SI-01',
        ]);
        $this->assertDatabaseHas('master_kegiatans', [
            'kode_kegiatan' => 'IF-WP6',
            'kode_prodi' => 'IF',
        ]);

        $activityCount = DB::table('master_kegiatans')->count();
        $programCount = DB::table('prodi')->count();
        $participantCount = DB::table('pesertas')->count();
        $studentUserCount = DB::table('users')->where('role', 'mahasiswa')->count();

        $this->assertGreaterThan(40, $activityCount);
        $this->assertGreaterThan(10, $programCount);
        $this->assertGreaterThan(600, $participantCount);
        $this->assertSame($participantCount, $studentUserCount);
        $this->assertDatabaseHas('pesertas', [
            'nim' => '152025083',
            'angkatan' => 2025,
            'kode_prodi' => 'IF',
        ]);
        $this->assertDatabaseHas('users', [
            'nim' => '152025083',
            'role' => 'mahasiswa',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@itenas.ac.id',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('dpp_access_requests', [
            'username_sikad' => 'dosen.testing',
            'approval_status' => 'disetujui',
        ]);

        $this->seed(KegiatanSeeder::class);
        $this->seed(MahasiswaSeeder::class);
        $this->seed();

        $this->assertSame($activityCount, DB::table('master_kegiatans')->count());
        $this->assertSame($programCount, DB::table('prodi')->count());
        $this->assertSame($participantCount, DB::table('pesertas')->count());
        $this->assertSame($studentUserCount, DB::table('users')->where('role', 'mahasiswa')->count());
    }
}
