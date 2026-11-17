<?php

namespace Tests\Feature;

use App\Models\DppAccessRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DppSikadAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint =
        'https://api-sikad.itenas.ac.id/public/4p151k4dn0w/dosen/login-dosen-pld';

    private function configureSikadApi(bool $success = true): void
    {
        config([
            'services.campus_auth.dosen_endpoint' => $this->endpoint,
            'services.campus_auth.api_key' => 'test-api-key',
            'services.campus_auth.timeout' => 10,
        ]);

        Http::fake([
            'api-sikad.itenas.ac.id/*' => Http::response(
                $success
                    ? [
                        'success' => true,
                        'message' => 'Login Berhasil',
                        'data' => [[
                            'nomor_dosen' => '11837',
                            'nama' => 'ANISA PUTRI SETYANINGRUM',
                            'kode_fakultas' => '1',
                            'kode_prodi' => '5',
                            'nidn' => '',
                            'gelar_depan' => '',
                            'gelar_belakang' => 'S.Kom., M.T.',
                            'status' => 'T',
                        ]],
                    ]
                    : [
                        'success' => false,
                        'message' => 'Login Gagal',
                    ],
                $success ? 200 : 401
            ),
        ]);
    }

    public function test_valid_dosen_credentials_create_pending_access_request(): void
    {
        $this->configureSikadApi();

        $response = $this->post(route('login'), [
            'login_type' => 'dosen',
            'identifier' => 'anisa.putri',
            'password' => 'sikad-password',
        ]);

        $response->assertRedirect(route('login'));

        $response->assertSessionHas(
            'status',
            'Identitas dosen berhasil diverifikasi. Silakan tunggu persetujuan Admin sebelum dapat mengakses sistem.'
        );

        $this->assertGuest();

        $this->assertDatabaseHas('dpp_access_requests', [
            'username_sikad' => 'anisa.putri',
            'nomor_dosen' => '11837',
            'nama' => 'ANISA PUTRI SETYANINGRUM',
            'kode_fakultas_sikad' => '1',
            'kode_prodi_sikad' => '5',
            'approval_status' => 'menunggu',
        ]);

        $this->assertDatabaseMissing('users', [
            'role' => 'dpp',
            'name' => 'ANISA PUTRI SETYANINGRUM',
        ]);

        Http::assertSent(fn ($request) =>
            $request->url() === $this->endpoint
            && $request['APIKEY'] === 'test-api-key'
            && $request['username'] === 'anisa.putri'
            && $request['password'] === 'sikad-password'
        );
    }

    public function test_approved_dosen_can_login_using_sikad_credentials(): void
    {
        $this->configureSikadApi();

        $admin = User::factory()->admin()->create();

        $prodi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create([
            'name' => 'ANISA PUTRI SETYANINGRUM',
            'email' => null,
        ]);

        $dpp->dppProfile()->create([
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        DppAccessRequest::create([
            'username_sikad' => 'anisa.putri',
            'nomor_dosen' => '11837',
            'nama' => 'ANISA PUTRI SETYANINGRUM',
            'kode_fakultas_sikad' => '1',
            'kode_prodi_sikad' => '5',
            'approval_status' => DppAccessRequest::STATUS_DISETUJUI,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'user_id' => $dpp->id,
        ]);

        $response = $this->post(route('login'), [
            'login_type' => 'dosen',
            'identifier' => 'anisa.putri',
            'password' => 'sikad-password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($dpp);
    }

    public function test_rejected_dosen_cannot_login(): void
    {
        $this->configureSikadApi();

        DppAccessRequest::create([
            'username_sikad' => 'anisa.putri',
            'nomor_dosen' => '11837',
            'nama' => 'ANISA PUTRI SETYANINGRUM',
            'approval_status' => DppAccessRequest::STATUS_DITOLAK,
            'rejection_reason' => 'Tidak memenuhi persyaratan.',
        ]);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'login_type' => 'dosen',
                'identifier' => 'anisa.putri',
                'password' => 'sikad-password',
            ]);

        $response->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_invalid_sikad_credentials_cannot_create_access_request(): void
    {
        $this->configureSikadApi(false);

        $response = $this->from(route('login'))
            ->post(route('login'), [
                'login_type' => 'dosen',
                'identifier' => 'anisa.putri',
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors('identifier');

        $this->assertGuest();

        $this->assertDatabaseCount('dpp_access_requests', 0);

        $this->assertDatabaseMissing('users', [
            'role' => 'dpp',
        ]);
    }

    public function test_numeric_sikad_username_can_create_pending_access_request(): void
    {
        $this->configureSikadApi();

        $response = $this->post(route('login'), [
            'identifier' => '11837',
            'password' => 'sikad-password',
        ]);

        $response->assertRedirect(route('login'));

        $response->assertSessionHas(
            'status',
            'Identitas dosen berhasil diverifikasi. Silakan tunggu persetujuan Admin sebelum dapat mengakses sistem.'
        );

        $this->assertGuest();

        $this->assertDatabaseHas('dpp_access_requests', [
            'username_sikad' => '11837',
            'nomor_dosen' => '11837',
            'approval_status' => 'menunggu',
        ]);
    }
}
