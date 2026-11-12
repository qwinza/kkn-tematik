<?php

namespace Tests\Feature\Admin;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DppManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dpp_list(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dpp.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/DPP/Index')
                ->has('dpps')
                ->has('prodis')
            );
    }

    public function test_admin_can_create_dpp_account(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.dpp.store'), [
                'name' => 'DPP Informatika',
                'email' => 'dpp.informatika@example.com',
                'kode_prodi' => $prodi->kode_prodi,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect();

        $user = User::where('email', 'dpp.informatika@example.com')->firstOrFail();

        $this->assertSame('dpp', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertSame($prodi->kode_prodi, $user->dppProfile->kode_prodi);
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
        $this->assertSame('dpp.updated@example.com', $dpp->fresh()->email);
        $this->assertSame($newProdi->kode_prodi, $dpp->fresh()->dppProfile->kode_prodi);
    }

    public function test_admin_can_reset_dpp_password(): void
    {
        $admin = User::factory()->admin()->create();
        $dpp = User::factory()->dpp()->create([
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.dpp.reset-password', $dpp), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect();

        $dpp->refresh();

        $this->assertTrue(Hash::check('NewPassword123!', $dpp->password));
        $this->assertTrue($dpp->must_change_password);
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