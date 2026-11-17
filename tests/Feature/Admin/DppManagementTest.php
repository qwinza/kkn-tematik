<?php

namespace Tests\Feature\Admin;

use App\Models\DppAccessRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DppManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dpp_list_and_pending_requests(): void
    {
        $admin = User::factory()->admin()->create();

        DppAccessRequest::create([
            'username_sikad' => 'dosen.test',
            'nomor_dosen' => '11837',
            'nama' => 'Dosen Pengujian',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dpp.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/DPP/Index')
                ->has('dpps')
                ->has('prodis')
                ->has('pendingRequests.data', 1)
                ->where('pendingCount', 1)
            );
    }

    public function test_admin_can_view_empty_dpp_list(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dpp.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/DPP/Index')
                ->has('dpps')
                ->has('prodis')
                ->where('pendingCount', 0)
            );
    }

    public function test_admin_can_approve_dosen_and_create_dpp_account(): void
    {
        $admin = User::factory()->admin()->create();

        $prodi = Prodi::factory()->create();

        $accessRequest = DppAccessRequest::create([
            'username_sikad' => 'dosen.anisa',
            'nomor_dosen' => '11837',
            'nama' => 'ANISA PUTRI SETYANINGRUM',
            'kode_fakultas_sikad' => '1',
            'kode_prodi_sikad' => '5',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.dpp.access-requests.approve', $accessRequest),
                [
                    'kode_prodi' => $prodi->kode_prodi,
                ]
            )
            ->assertRedirect();

        $accessRequest->refresh();

        $this->assertSame(
            DppAccessRequest::STATUS_DISETUJUI,
            $accessRequest->approval_status
        );

        $this->assertSame($admin->id, $accessRequest->reviewed_by);
        $this->assertNotNull($accessRequest->reviewed_at);
        $this->assertNotNull($accessRequest->user_id);

        $dpp = User::query()->findOrFail($accessRequest->user_id);

        $this->assertTrue($dpp->isDpp());
        $this->assertNull($dpp->email);
        $this->assertSame(
            'ANISA PUTRI SETYANINGRUM',
            $dpp->name
        );

        $this->assertSame(
            $prodi->kode_prodi,
            $dpp->dppProfile->kode_prodi
        );

        $this->assertDatabaseHas('users', [
            'id' => $dpp->id,
            'role' => 'dpp',
            'email' => null,
        ]);

        $this->assertDatabaseHas('dpp_profiles', [
            'user_id' => $dpp->id,
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $this->assertDatabaseHas('dpp_access_requests', [
            'id' => $accessRequest->id,
            'approval_status' => 'disetujui',
            'user_id' => $dpp->id,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_reuses_existing_dpp_account_when_sikad_username_matches_email(): void
    {
        $admin = User::factory()->admin()->create();

        $oldProdi = Prodi::factory()->create();
        $newProdi = Prodi::factory()->create();

        $existingDpp = User::factory()->dpp()->create([
            'name' => 'DPP Lama',
            'email' => 'legacy.dpp@example.com',
        ]);

        $existingDpp->dppProfile()->create([
            'kode_prodi' => $oldProdi->kode_prodi,
        ]);

        $accessRequest = DppAccessRequest::create([
            'username_sikad' => 'legacy.dpp@example.com',
            'nomor_dosen' => '11839',
            'nama' => 'NAMA DOSEN SIKAD',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $dppCountBefore = User::where('role', 'dpp')->count();

        $this->actingAs($admin)
            ->post(
                route('admin.dpp.access-requests.approve', $accessRequest),
                [
                    'kode_prodi' => $newProdi->kode_prodi,
                ]
            )
            ->assertRedirect();

        $accessRequest->refresh();
        $existingDpp->refresh();

        $this->assertSame(
            DppAccessRequest::STATUS_DISETUJUI,
            $accessRequest->approval_status
        );

        $this->assertSame(
            $existingDpp->id,
            $accessRequest->user_id
        );

        $this->assertSame(
            'NAMA DOSEN SIKAD',
            $existingDpp->name
        );

        $this->assertSame(
            'legacy.dpp@example.com',
            $existingDpp->email
        );

        $this->assertSame(
            $newProdi->kode_prodi,
            $existingDpp->dppProfile()->value('kode_prodi')
        );

        $this->assertSame(
            $dppCountBefore,
            User::where('role', 'dpp')->count(),
            'Approval tidak boleh membuat akun DPP duplikat.'
        );

        $this->assertDatabaseHas('dpp_access_requests', [
            'id' => $accessRequest->id,
            'user_id' => $existingDpp->id,
            'approval_status' => 'disetujui',
        ]);
    }
    public function test_admin_cannot_approve_already_reviewed_request(): void
    {
        $admin = User::factory()->admin()->create();

        $prodi = Prodi::factory()->create();

        $accessRequest = DppAccessRequest::create([
            'username_sikad' => 'dosen.test',
            'nomor_dosen' => '11837',
            'nama' => 'Dosen Pengujian',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.dpp.access-requests.approve', $accessRequest),
                [
                    'kode_prodi' => $prodi->kode_prodi,
                ]
            )
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(
                route('admin.dpp.access-requests.approve', $accessRequest),
                [
                    'kode_prodi' => $prodi->kode_prodi,
                ]
            )
            ->assertStatus(409);

        $this->assertDatabaseCount('users', 2);
        // Dua akun: Admin dan satu DPP yang sudah dibuat.
    }

    public function test_admin_can_reject_dosen_access_request(): void
    {
        $admin = User::factory()->admin()->create();

        $accessRequest = DppAccessRequest::create([
            'username_sikad' => 'dosen.test',
            'nomor_dosen' => '11837',
            'nama' => 'Dosen Pengujian',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.dpp.access-requests.reject', $accessRequest),
                [
                    'rejection_reason' => 'Data dosen tidak memenuhi persyaratan.',
                ]
            )
            ->assertRedirect();

        $accessRequest->refresh();

        $this->assertSame(
            DppAccessRequest::STATUS_DITOLAK,
            $accessRequest->approval_status
        );

        $this->assertSame($admin->id, $accessRequest->reviewed_by);

        $this->assertSame(
            'Data dosen tidak memenuhi persyaratan.',
            $accessRequest->rejection_reason
        );

        $this->assertNotNull($accessRequest->reviewed_at);
        $this->assertNull($accessRequest->user_id);

        $this->assertDatabaseMissing('users', [
            'role' => 'dpp',
            'name' => 'Dosen Pengujian',
        ]);
    }

    public function test_non_admin_cannot_approve_dosen_request(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $prodi = Prodi::factory()->create();

        $accessRequest = DppAccessRequest::create([
            'username_sikad' => 'dosen.test',
            'nomor_dosen' => '11837',
            'nama' => 'Dosen Pengujian',
            'approval_status' => DppAccessRequest::STATUS_MENUNGGU,
        ]);

        $this->actingAs($mahasiswa)
            ->post(
                route('admin.dpp.access-requests.approve', $accessRequest),
                [
                    'kode_prodi' => $prodi->kode_prodi,
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseHas('dpp_access_requests', [
            'id' => $accessRequest->id,
            'approval_status' => 'menunggu',
        ]);
    }


    public function test_admin_can_update_dpp_account_and_prodi(): void
    {
        $admin = User::factory()->admin()->create();

        $oldProdi = Prodi::factory()->create();
        $newProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $oldProdi->kode_prodi,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.dpp.update', $dpp), [
                'name' => 'DPP Updated',
                'email' => 'dpp.updated@example.com',
                'kode_prodi' => $newProdi->kode_prodi,
            ])
            ->assertRedirect();

        $this->assertSame('DPP Updated', $dpp->fresh()->name);

        $this->assertSame(
            'dpp.updated@example.com',
            $dpp->fresh()->email
        );

        $this->assertSame(
            $newProdi->kode_prodi,
            $dpp->fresh()->dppProfile->kode_prodi
        );
    }


    public function test_admin_can_delete_dpp_account(): void
    {
        $admin = User::factory()->admin()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => Prodi::factory()->create()->kode_prodi,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.dpp.destroy', $dpp))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', [
            'id' => $dpp->id,
        ]);

        $this->assertDatabaseMissing('dpp_profiles', [
            'user_id' => $dpp->id,
        ]);
    }

    public function test_mahasiswa_cannot_access_dpp_management(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)
            ->get(route('admin.dpp.index'))
            ->assertForbidden();
    }

    public function test_dpp_cannot_access_dpp_management(): void
    {
        $dpp = User::factory()->dpp()->create();

        $this->actingAs($dpp)
            ->get(route('admin.dpp.index'))
            ->assertForbidden();
    }
}
