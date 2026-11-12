<?php

namespace Tests\Feature\Auth;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $prodi = Prodi::factory()->create();

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Register')
                ->has('prodis', 1)
                ->where('prodis.0.kode_prodi', $prodi->kode_prodi)
            );
    }

    public function test_student_registration_creates_a_guest_account_with_a_temporary_password(): void
    {
        $prodi = Prodi::factory()->create();

        $response = $this->post('/register', [
            'nama_lengkap' => 'Test Mahasiswa',
            'nim' => '202600000001',
            'email' => 'test@example.com',
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(
            'success',
            'Pendaftaran berhasil. Password sementara Anda adalah password123. Silakan masuk kembali menggunakan NRP, lalu ubah password melalui menu Profil.'
        );
        $this->assertGuest();

        $user = User::query()->where('nim', '202600000001')->firstOrFail();
        $this->assertSame('mahasiswa', $user->role);
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('pesertas', [
            'user_id' => $user->id,
            'nama_lengkap' => 'Test Mahasiswa',
            'nim' => '202600000001',
            'kode_prodi' => $prodi->kode_prodi,
            'program_studi' => $prodi->nama_prodi,
        ]);

        $this->post(route('login'), [
            'identifier' => '202600000001',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_an_existing_nrp(): void
    {
        $prodi = Prodi::factory()->create();
        User::factory()->mahasiswa()->create(['nim' => '202600000001']);

        $this->from('/register')->post('/register', [
            'nama_lengkap' => 'Mahasiswa Baru',
            'nim' => '202600000001',
            'email' => 'new@example.com',
            'kode_prodi' => $prodi->kode_prodi,
        ])->assertSessionHasErrors('nim');

        $this->assertGuest();
        $this->assertDatabaseCount('pesertas', 0);
    }
}
