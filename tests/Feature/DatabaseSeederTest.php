<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_loads_the_full_activity_catalog_and_can_be_repeated(): void
    {
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

        $this->assertGreaterThan(40, $activityCount);
        $this->assertGreaterThan(10, $programCount);

        $this->seed();

        $this->assertSame($activityCount, DB::table('master_kegiatans')->count());
        $this->assertSame($programCount, DB::table('prodi')->count());
    }
}
