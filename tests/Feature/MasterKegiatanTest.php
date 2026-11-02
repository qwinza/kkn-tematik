<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterKegiatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_master_kegiatan_list(): void
    {
        $user = User::factory()->admin()->create();
        MasterKegiatan::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('master-kegiatan.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('MasterKegiatan/Index')
            ->has('kegiatans.data', 3)
            ->where('canManage', true)
        );
    }

    public function test_admin_can_create_master_kegiatan(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();

        $response = $this->actingAs($admin)->post(route('master-kegiatan.store'), [
            'kode_kegiatan' => 'KKN-101',
            'nama_kegiatan' => 'Pemberdayaan Masyarakat Pesisir',
            'kategori' => 'Pemberdayaan',
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_kegiatans', [
            'kode_kegiatan' => 'KKN-101',
            'nama_kegiatan' => 'Pemberdayaan Masyarakat Pesisir',
            'kategori' => 'Pemberdayaan',
            'kode_prodi' => $prodi->kode_prodi,
        ]);
    }

    public function test_mahasiswa_cannot_create_master_kegiatan(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($mahasiswa)->post(route('master-kegiatan.store'), [
            'kode_kegiatan' => 'KKN-102',
            'nama_kegiatan' => 'Pelatihan Komputer Desa',
            'kategori' => 'Teknologi',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('master_kegiatans', [
            'kode_kegiatan' => 'KKN-102',
        ]);
    }

    public function test_admin_can_update_master_kegiatan(): void
    {
        $admin = User::factory()->admin()->create();
        $kegiatan = MasterKegiatan::factory()->create([
            'kode_kegiatan' => 'KKN-OLD',
            'nama_kegiatan' => 'Kegiatan Lama',
            'kategori' => 'Umum',
        ]);

        $response = $this->actingAs($admin)->put(route('master-kegiatan.update', $kegiatan), [
            'kode_kegiatan' => 'KKN-NEW',
            'nama_kegiatan' => 'Kegiatan Baru Diperbarui',
            'kategori' => 'Sosial',
            'kode_prodi' => $kegiatan->kode_prodi,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_kegiatans', [
            'id' => $kegiatan->id,
            'kode_kegiatan' => 'KKN-NEW',
            'nama_kegiatan' => 'Kegiatan Baru Diperbarui',
            'kategori' => 'Sosial',
            'kode_prodi' => $kegiatan->kode_prodi,
        ]);
    }

    public function test_admin_can_delete_master_kegiatan_without_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $kegiatan = MasterKegiatan::factory()->create();

        $response = $this->actingAs($admin)->delete(route('master-kegiatan.destroy', $kegiatan));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('master_kegiatans', ['id' => $kegiatan->id]);
    }

    public function test_admin_cannot_delete_master_kegiatan_with_existing_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $kegiatan = MasterKegiatan::factory()->create();
        $peserta = Peserta::factory()->create();

        LogKegiatan::factory()->create([
            'master_kegiatan_id' => $kegiatan->id,
            'peserta_id' => $peserta->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('master-kegiatan.destroy', $kegiatan));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('master_kegiatans', ['id' => $kegiatan->id]);
    }
}
