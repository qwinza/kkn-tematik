<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DppLocalAccountRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dpp_profile_hides_local_password_and_delete_forms(): void
    {
        $dpp = User::factory()->dpp()->create();

        $this->actingAs($dpp)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Profile/Edit')
                ->where('isDpp', true)
                ->where('canManageLocalPassword', false)
                ->where('canDeleteAccount', false)
            );
    }

    public function test_dpp_cannot_update_local_password(): void
    {
        $dpp = User::factory()->dpp()->create();

        $this->actingAs($dpp)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertForbidden();

        $this->assertTrue(
            Hash::check('password', $dpp->fresh()->password)
        );
    }

    public function test_dpp_cannot_delete_account_from_profile(): void
    {
        $dpp = User::factory()->dpp()->create();

        $this->actingAs($dpp)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $dpp->id,
            'role' => 'dpp',
        ]);
    }

    public function test_dpp_cannot_use_first_login_password_feature(): void
    {
        $dpp = User::factory()->dpp()->create([
            'must_change_password' => true,
        ]);

        $this->actingAs($dpp)
            ->get(route('password.first-login.edit'))
            ->assertForbidden();

        $this->actingAs($dpp)
            ->put(route('password.first-login.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertForbidden();

        $this->assertTrue($dpp->fresh()->must_change_password);

        $this->assertTrue($dpp->fresh()->must_change_password);

        $this->assertTrue(
            Hash::check('password', $dpp->fresh()->password));

        $this->assertFalse(
            Hash::check('NewPassword123!', $dpp->fresh()->password));
    }
}
