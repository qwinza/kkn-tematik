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

    public function test_admin_can_sort_master_kegiatan_by_name_ascending(): void
    {
        $admin = User::factory()->admin()->create();
        $zKegiatan = MasterKegiatan::factory()->create(['nama_kegiatan' => 'Z Kegiatan']);
        $aKegiatan = MasterKegiatan::factory()->create(['nama_kegiatan' => 'A Kegiatan']);

        $response = $this->actingAs($admin)->get(route('master-kegiatan.index', [
            'sort' => 'nama_kegiatan',
            'direction' => 'asc',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('MasterKegiatan/Index')
            ->where('kegiatans.data.0.id', $aKegiatan->id)
            ->where('kegiatans.data.1.id', $zKegiatan->id)
            ->where('filters.sort', 'nama_kegiatan')
            ->where('filters.direction', 'asc')
        );
    }

    public function test_admin_can_filter_master_kegiatan_by_prodi(): void
    {
        $admin = User::factory()->admin()->create();
        $prodiIf = Prodi::factory()->create(['nama_prodi' => 'Teknik Informatika']);
        $prodiArs = Prodi::factory()->create(['nama_prodi' => 'Arsitektur']);
        $kegiatanIf = MasterKegiatan::factory()->create(['kode_prodi' => $prodiIf->kode_prodi]);
        MasterKegiatan::factory()->create(['kode_prodi' => $prodiArs->kode_prodi]);

        $response = $this->actingAs($admin)->get(route('master-kegiatan.index', [
            'kode_prodi' => $prodiIf->kode_prodi,
            'sort' => 'id',
            'direction' => 'desc',
        ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('MasterKegiatan/Index')
            ->has('kegiatans.data', 1)
            ->where('kegiatans.data.0.id', $kegiatanIf->id)
            ->where('filters.kode_prodi', $prodiIf->kode_prodi)
            ->where('filters.sort', 'id')
            ->where('filters.direction', 'desc')
        );
    }

    public function test_admin_can_search_master_kegiatan_by_prodi_name(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create(['nama_prodi' => 'Arsitektur']);
        $kegiatan = MasterKegiatan::factory()->create(['kode_prodi' => $prodi->kode_prodi]);
        MasterKegiatan::factory()->create();

        $response = $this->actingAs($admin)->get(route('master-kegiatan.index', ['search' => 'Arsitektur']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('MasterKegiatan/Index')
            ->has('kegiatans.data', 1)
            ->where('kegiatans.data.0.id', $kegiatan->id)
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
    public function test_dpp_only_sees_master_kegiatan_from_own_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $ownActivity = MasterKegiatan::factory()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        MasterKegiatan::factory()->create([
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $this->actingAs($dpp)
            ->get(route('master-kegiatan.index', [
                'kode_prodi' => $otherProdi->kode_prodi,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterKegiatan/Index')
                ->has('kegiatans.data', 1)
                ->where('kegiatans.data.0.id', $ownActivity->id)
                ->where('canManage', true)
                ->where('isDpp', true)
                ->has('prodis', 1)
                ->where('prodis.0.kode_prodi', $ownProdi->kode_prodi)
            );
    }

    public function test_dpp_can_create_master_kegiatan_only_for_own_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $this->actingAs($dpp)
            ->post(route('master-kegiatan.store'), [
                'kode_kegiatan' => 'DPP-001',
                'nama_kegiatan' => 'Kegiatan Prodi DPP',
                'kategori' => 'Pemberdayaan',
                'kode_prodi' => $otherProdi->kode_prodi,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('master_kegiatans', [
            'kode_kegiatan' => 'DPP-001',
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $this->assertDatabaseMissing('master_kegiatans', [
            'kode_kegiatan' => 'DPP-001',
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);
    }

    public function test_dpp_cannot_update_master_kegiatan_from_another_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $otherActivity = MasterKegiatan::factory()->create([
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $this->actingAs($dpp)
            ->put(route('master-kegiatan.update', $otherActivity), [
                'kode_kegiatan' => 'DPP-UPDATE',
                'nama_kegiatan' => 'Percobaan Perubahan',
                'kategori' => 'Umum',
                'kode_prodi' => $ownProdi->kode_prodi,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('master_kegiatans', [
            'id' => $otherActivity->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);
    }

    public function test_dpp_cannot_delete_master_kegiatan_from_another_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $otherActivity = MasterKegiatan::factory()->create([
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $this->actingAs($dpp)
            ->delete(route('master-kegiatan.destroy', $otherActivity))
            ->assertForbidden();

        $this->assertDatabaseHas('master_kegiatans', [
            'id' => $otherActivity->id,
        ]);
    }

    public function test_dpp_can_update_master_kegiatan_from_own_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $activity = MasterKegiatan::factory()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $this->actingAs($dpp)
            ->put(route('master-kegiatan.update', $activity), [
                'kode_kegiatan' => 'DPP-UPDATED',
                'nama_kegiatan' => 'Kegiatan Diperbarui',
                'kategori' => 'Sosial',
                'kode_prodi' => $ownProdi->kode_prodi,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('master_kegiatans', [
            'id' => $activity->id,
            'kode_kegiatan' => 'DPP-UPDATED',
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);
    }
}
