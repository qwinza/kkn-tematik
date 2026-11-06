<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProdiTest extends TestCase
{
    use RefreshDatabase;

    public function test_prodi_is_the_only_program_study_table(): void
    {
        $this->assertTrue(Schema::hasTable('prodi'));
        $this->assertFalse(Schema::hasTable('program_studis'));
        $this->assertTrue(Schema::hasColumn('prodi', 'fakultas'));
        $this->assertFalse(Schema::hasColumn('pesertas', 'program_studi_id'));
    }

    public function test_admin_can_create_prodi_with_official_code_and_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('prodi.store'), [
            'kode_prodi' => 'TI-01',
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prodi', [
            'kode_prodi' => 'TI-01',
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);
    }

    public function test_fakultas_is_required_when_creating_prodi(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('prodi.store'), [
            'kode_prodi' => 'TI-02',
            'nama_prodi' => 'Teknik Informatika',
        ]);

        $response->assertSessionHasErrors('fakultas');
        $this->assertDatabaseMissing('prodi', ['kode_prodi' => 'TI-02']);
    }

    public function test_peserta_belongs_to_prodi_by_kode_prodi(): void
    {
        $prodi = Prodi::factory()->create(['fakultas' => 'FTI']);
        $peserta = Peserta::factory()->create(['kode_prodi' => $prodi->kode_prodi]);

        $this->assertSame($prodi->kode_prodi, $peserta->prodi->kode_prodi);
        $this->assertTrue($prodi->pesertas->contains($peserta));
    }

    public function test_non_admin_cannot_manage_prodi(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)->get(route('prodi.index'))->assertForbidden();
    }

    public function test_admin_can_filter_prodi_by_faculty_and_sort_names_descending(): void
    {
        $admin = User::factory()->admin()->create();
        Prodi::factory()->create(['kode_prodi' => 'TI-01', 'nama_prodi' => 'A Informatika', 'fakultas' => 'FTI']);
        Prodi::factory()->create(['kode_prodi' => 'TI-02', 'nama_prodi' => 'Z Informatika', 'fakultas' => 'FTI']);
        Prodi::factory()->create(['kode_prodi' => 'ARS-01', 'nama_prodi' => 'Arsitektur', 'fakultas' => 'FAD']);

        $response = $this->actingAs($admin)->get(route('prodi.index', [
            'fakultas' => 'FTI',
            'sort' => 'nama_prodi',
            'direction' => 'desc',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Prodi/Index')
            ->has('prodis', 2)
            ->where('prodis.0.nama_prodi', 'Z Informatika')
            ->where('filters.fakultas', 'FTI')
            ->where('filters.direction', 'desc')
        );
    }

    public function test_prodi_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();
        Peserta::factory()->create(['kode_prodi' => $prodi->kode_prodi]);

        $response = $this->actingAs($admin)->delete(route('prodi.destroy', $prodi));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('prodi', ['kode_prodi' => $prodi->kode_prodi]);
    }
}
