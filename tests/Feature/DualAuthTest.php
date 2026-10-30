<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DualAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_using_email(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@itenas.ac.id',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login'), [
            'identifier' => 'admin@itenas.ac.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_cannot_login_with_invalid_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@itenas.ac.id',
            'password' => Hash::make('password'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => 'admin@itenas.ac.id',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_non_admin_cannot_login_via_email(): void
    {
        User::factory()->mahasiswa()->create([
            'email' => 'student@itenas.ac.id',
            'password' => Hash::make('password'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => 'student@itenas.ac.id',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_mahasiswa_can_login_using_dummy_api_and_creates_shadow_user_and_peserta(): void
    {
        $nim = '152024038';
        $password = 'password123';

        // Pastikan database awal belum memiliki user ini
        $this->assertDatabaseMissing('users', ['nim' => $nim]);
        $this->assertDatabaseMissing('pesertas', ['nim' => $nim]);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        // Cek user berhasil diautentikasi
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertNotNull($user);
        $this->assertSame($nim, $user->nim);
        $this->assertSame('mahasiswa', $user->role);
        $this->assertSame('Aa Faris Ahmad Shidiq', $user->name);

        // Cek data profil di tabel pesertas terbuat dan berelasi
        $this->assertDatabaseHas('pesertas', [
            'user_id' => $user->id,
            'nim' => $nim,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'program_studi' => 'Teknik Informatika',
        ]);

        $this->assertNotNull($user->peserta);
        $this->assertSame('Teknik Informatika', $user->peserta->program_studi);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_mahasiswa_existing_user_can_login_via_dummy_api(): void
    {
        $nim = '152024038';
        $password = 'password123';

        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'email' => null,
            'password' => Hash::make('old-password'),
            'role' => 'mahasiswa',
        ]);

        Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'program_studi' => 'Teknik Informatika',
        ]);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        $this->assertAuthenticatedAs($user->fresh());
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_mahasiswa_cannot_login_with_invalid_credentials_on_dummy_api(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => '152024038',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_models_and_eloquent_relationships(): void
    {
        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
        ]);

        $masterKegiatan = MasterKegiatan::create([
            'kode_kegiatan' => 'KKN-001',
            'nama_kegiatan' => 'Sosialisasi Program Kerja KKN',
            'kategori' => 'Sosialisasi',
        ]);

        $logKegiatan = LogKegiatan::create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $masterKegiatan->id,
            'tanggal_kehadiran' => '2026-10-30',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'lokasi_kegiatan' => 'Kampus ITENAS',
            'deskripsi_kegiatan' => 'Mengikuti pembekalan dan sosialisasi program KKN.',
            'foto_kegiatan_path' => 'photos/kkn1.jpg',
            'latitude' => -6.89750000,
            'longitude' => 107.63580000,
            'deskripsi_luaran' => 'Laporan awal rencana kegiatan KKN.',
            'file_luaran_path' => 'reports/rencana.pdf',
        ]);

        // Cek relasi dari User ke Peserta
        $this->assertSame($peserta->id, $user->peserta->id);

        // Cek relasi dari Peserta ke User
        $this->assertSame($user->id, $peserta->user->id);

        // Cek relasi dari Peserta ke LogKegiatan
        $this->assertTrue($peserta->logKegiatans->contains($logKegiatan));

        // Cek relasi dari MasterKegiatan ke LogKegiatan
        $this->assertTrue($masterKegiatan->logKegiatans->contains($logKegiatan));

        // Cek relasi dari LogKegiatan ke Peserta dan MasterKegiatan
        $this->assertSame($peserta->id, $logKegiatan->peserta->id);
        $this->assertSame($masterKegiatan->id, $logKegiatan->masterKegiatan->id);
    }
}
