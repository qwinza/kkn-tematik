<?php

namespace Tests\Feature\Admin;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MahasiswaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_mahasiswa_with_profile_details(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.mahasiswa.store'), [
                'nama_lengkap' => 'Mahasiswa Contoh',
                'nim' => '152024999',
                'email' => 'mahasiswa@example.test',
                'no_telepon' => '081234567890',
                'angkatan' => '2024',
                'kode_prodi' => $prodi->kode_prodi,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $peserta = Peserta::query()->where('nim', '152024999')->firstOrFail();

        $this->assertSame('mahasiswa@example.test', $peserta->email);
        $this->assertSame('081234567890', $peserta->no_telepon);
        $this->assertSame(2024, $peserta->angkatan);
        $this->assertSame('mahasiswa@example.test', $peserta->user->email);
    }

    public function test_admin_can_update_mahasiswa_profile_details(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();
        $student = User::factory()->mahasiswa()->create([
            'email' => 'lama@example.test',
        ]);
        $peserta = Peserta::factory()->create([
            'user_id' => $student->id,
            'email' => 'lama@example.test',
            'no_telepon' => '081111111111',
            'angkatan' => 2022,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.mahasiswa.update', $peserta), [
                'nama_lengkap' => $peserta->nama_lengkap,
                'nim' => $peserta->nim,
                'email' => 'baru@example.test',
                'no_telepon' => '081234567890',
                'angkatan' => '2025',
                'kode_prodi' => $prodi->kode_prodi,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('baru@example.test', $peserta->fresh()->email);
        $this->assertSame('081234567890', $peserta->fresh()->no_telepon);
        $this->assertSame(2025, $peserta->fresh()->angkatan);
        $this->assertSame('baru@example.test', $student->fresh()->email);
    }

    public function test_admin_must_provide_a_valid_four_digit_angkatan(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.mahasiswa.index'))
            ->post(route('admin.mahasiswa.store'), [
                'nama_lengkap' => 'Mahasiswa Contoh',
                'nim' => '152024999',
                'email' => null,
                'angkatan' => '24',
                'kode_prodi' => $prodi->kode_prodi,
            ])
            ->assertSessionHasErrors('angkatan')
            ->assertRedirect(route('admin.mahasiswa.index'));

        $this->assertDatabaseMissing('pesertas', ['nim' => '152024999']);
    }
}
