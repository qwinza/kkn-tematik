<?php

namespace Tests\Feature\Auth;

use App\Models\Prodi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_student_registration_page_remains_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_guest_cannot_create_student_account_through_public_registration(): void
    {
        $prodi = Prodi::factory()->create();

        $this->post('/register', [
            'nama_lengkap' => 'Test Mahasiswa',
            'nim' => '202600000001',
            'email' => 'test@example.com',
            'kode_prodi' => $prodi->kode_prodi,
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('pesertas', 0);
    }
}