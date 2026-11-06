<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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

    public function test_registered_mahasiswa_can_login_and_missing_peserta_profile_is_created(): void
    {
        $nim = '152024038';
        $password = 'password123';

        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'password' => Hash::make($password),
            'role' => 'mahasiswa',
        ]);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        // Cek user berhasil diautentikasi
        $this->assertAuthenticatedAs($user);

        // Cek data profil di tabel pesertas terbuat dan berelasi
        $this->assertDatabaseHas('pesertas', [
            'user_id' => $user->id,
            'nim' => $nim,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'kode_prodi' => null,
        ]);

        $this->assertNotNull($user->peserta);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_unregistered_mahasiswa_cannot_login_or_be_created_automatically(): void
    {
        $nim = '152025091';

        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => $nim,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
        $this->assertDatabaseMissing('users', ['nim' => $nim]);
        $this->assertDatabaseMissing('pesertas', ['nim' => $nim]);
    }

    public function test_registered_mahasiswa_cannot_login_with_wrong_password(): void
    {
        $nim = '152024038';

        User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'password' => Hash::make('correct-password'),
            'role' => 'mahasiswa',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => $nim,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_seeded_farid_annas_can_login_with_his_registered_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->post(route('login'), [
            'identifier' => '152025083',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('pesertas', [
            'nim' => '152025083',
            'nama_lengkap' => 'Farid Annas',
            'program_studi' => 'Sistem Informasi',
            'kode_prodi' => 'SI-01',
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_mahasiswa_with_registered_password_can_login(): void
    {
        $nim = '152024038';
        $password = 'password123';

        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'email' => null,
            'password' => Hash::make($password),
            'role' => 'mahasiswa',
        ]);

        Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
        ]);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        $this->assertAuthenticatedAs($user->fresh());
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_mahasiswa_cannot_login_with_invalid_credentials(): void
    {
        $nim = '152024038';
        User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'password' => Hash::make('correct-password'),
            'role' => 'mahasiswa',
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'identifier' => $nim,
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

        $prodi = Prodi::factory()->create();
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $masterKegiatan = MasterKegiatan::create([
            'kode_kegiatan' => 'KKN-001',
            'nama_kegiatan' => 'Sosialisasi Program Kerja KKN',
            'kategori' => 'Sosialisasi',
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $logKegiatan = LogKegiatan::create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $masterKegiatan->id,
            'tanggal_kehadiran' => '2026-10-30',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'lokasi_kegiatan' => 'Kampus ITENAS',
            'deskripsi_kegiatan' => 'Mengikuti pembekalan dan sosialisasi program KKN.',
            'latitude' => -6.89750000,
            'longitude' => 107.63580000,
            'deskripsi_luaran' => 'Laporan awal rencana kegiatan KKN.',
        ]);

        // Cek relasi dari User ke Peserta
        $this->assertSame($peserta->id, $user->peserta->id);

        // Cek relasi dari Peserta ke User
        $this->assertSame($user->id, $peserta->user->id);
        $this->assertSame($prodi->kode_prodi, $peserta->prodi->kode_prodi);

        // Cek relasi dari Peserta ke LogKegiatan
        $this->assertTrue($peserta->logKegiatans->contains($logKegiatan));

        // Cek relasi dari MasterKegiatan ke LogKegiatan
        $this->assertTrue($masterKegiatan->logKegiatans->contains($logKegiatan));

        // Cek relasi dari LogKegiatan ke Peserta dan MasterKegiatan
        $this->assertSame($peserta->id, $logKegiatan->peserta->id);
        $this->assertSame($masterKegiatan->id, $logKegiatan->masterKegiatan->id);
    }
}
