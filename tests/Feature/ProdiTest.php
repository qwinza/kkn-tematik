<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_prodi_with_official_code_and_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('prodi.store'), [
            'kode_prodi' => 'TI-01',
            'nama_prodi' => 'Teknik Informatika',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('prodi', [
            'kode_prodi' => 'TI-01',
            'nama_prodi' => 'Teknik Informatika',
        ]);
    }

    public function test_non_admin_cannot_manage_prodi(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)->get(route('prodi.index'))->assertForbidden();
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
