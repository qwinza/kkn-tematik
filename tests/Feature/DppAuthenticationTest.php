<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DppAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dpp_can_login_using_email_and_local_password(): void
    {
        $prodi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create([
            'email' => 'dpp@example.com',
            'password' => Hash::make('password123'),
        ]);

        $dpp->dppProfile()->create([
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $response = $this->post('/login', [
            'identifier' => 'dpp@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($dpp);
    }

    public function test_dpp_without_profile_cannot_login(): void
    {
        User::factory()->dpp()->create([
            'email' => 'dpp@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'dpp@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_mahasiswa_cannot_login_using_email(): void
    {
        User::factory()->mahasiswa()->create([
            'email' => 'mahasiswa@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'identifier' => 'mahasiswa@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }
}