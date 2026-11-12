<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FirstLoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_with_legacy_password_flag_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'Mahasiswa Dummy',
            'nim' => '202600000001',
            'role' => 'mahasiswa',
            'must_change_password' => true,
            'password' => 'password12345',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('password.first-login.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/FirstLoginPassword')
            );
    }

    public function test_student_can_replace_temporary_password_and_clear_required_flag(): void
    {
        $user = User::factory()->create([
            'name' => 'Mahasiswa Dummy',
            'nim' => '202600000001',
            'role' => 'mahasiswa',
            'must_change_password' => true,
            'password' => 'password12345',
        ]);

        $this->actingAs($user)
            ->put(route('password.first-login.update'), [
                'current_password' => 'password12345',
                'password' => 'PasswordBaru123!',
                'password_confirmation' => 'PasswordBaru123!',
            ])
            ->assertRedirect(route('dashboard'));

        $freshUser = $user->fresh();

        $this->assertFalse($freshUser->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaru123!', $freshUser->password));

        $this->actingAs($freshUser)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_student_cannot_complete_first_login_change_with_wrong_temporary_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Mahasiswa Dummy',
            'nim' => '202600000001',
            'role' => 'mahasiswa',
            'must_change_password' => true,
            'password' => 'password12345',
        ]);

        $this->actingAs($user)
            ->from(route('password.first-login.edit'))
            ->put(route('password.first-login.update'), [
                'current_password' => 'password-salah',
                'password' => 'PasswordBaru123!',
                'password_confirmation' => 'PasswordBaru123!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_student_without_email_can_access_dashboard_after_changing_password(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'email_verified_at' => null,
            'nim' => '202600000002',
            'role' => 'mahasiswa',
            'must_change_password' => true,
            'password' => 'password12345',
        ]);

        $this->actingAs($user)
            ->put(route('password.first-login.update'), [
                'current_password' => 'password12345',
                'password' => 'PasswordBaru123!',
                'password_confirmation' => 'PasswordBaru123!',
            ])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }
}
