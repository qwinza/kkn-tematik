<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use App\Services\PythonAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StudentActivityPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_download_pdf_containing_only_their_own_activities(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $prodi = Prodi::factory()->create();

        $peserta = Peserta::factory()->create([
            'user_id' => $user->id,
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $master = MasterKegiatan::factory()->create([
            'kode_prodi' => $prodi->kode_prodi,
            'nama_kegiatan' => 'Sosialisasi Pengelolaan Sampah',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-01',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'status' => 'disetujui',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-02',
            'status' => 'menunggu',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-03',
            'status' => 'ditolak',
        ]);

        LogKegiatan::factory()->draft()->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-04',
        ]);

        $otherUser = User::factory()->mahasiswa()->create();
        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'master_kegiatan_id' => $master->id,
            'status' => 'disetujui',
        ]);

        $fakePdf = "%PDF-1.4\nstudent-report\n%%EOF";
        $capturedPayload = null;

        $python = Mockery::mock(PythonAnalyticsService::class);

        $python->shouldReceive('generateStudentActivityPdf')
            ->once()
            ->andReturnUsing(function (array $payload) use (
                &$capturedPayload,
                $fakePdf
            ): string {
                $capturedPayload = $payload;

                return $fakePdf;
            });

        $this->app->instance(PythonAnalyticsService::class, $python);

        $response = $this->actingAs($user)
            ->get(route('log-kegiatan.export-student-pdf'));

        $response->assertOk();

        $this->assertSame(
            'application/pdf',
            $response->headers->get('Content-Type')
        );

        $this->assertStringContainsString(
            'attachment;',
            (string) $response->headers->get('Content-Disposition')
        );

        $this->assertSame($fakePdf, $response->getContent());

        $this->assertNotNull($capturedPayload);
        $this->assertSame(
            $peserta->nama_lengkap,
            $capturedPayload['mahasiswa']['nama']
        );
        $this->assertSame(
            $peserta->nim,
            $capturedPayload['mahasiswa']['nim']
        );
        $this->assertSame(
            $prodi->nama_prodi,
            $capturedPayload['mahasiswa']['prodi']
        );

        $this->assertCount(4, $capturedPayload['kegiatan']);

        $this->assertSame(
            ['draft', 'ditolak', 'menunggu', 'disetujui'],
            array_column($capturedPayload['kegiatan'], 'status')
        );

        $this->assertSame(
            'Sosialisasi Pengelolaan Sampah',
            $capturedPayload['kegiatan'][3]['nama_kegiatan']
        );

        $this->assertSame(
            2.0,
            $capturedPayload['kegiatan'][3]['durasi_jam']
        );

        $serializedPayload = json_encode($capturedPayload);

        $this->assertStringNotContainsString(
            $otherPeserta->nim,
            $serializedPayload
        );
    }

    public function test_admin_and_dpp_cannot_download_student_pdf(): void
    {
        $admin = User::factory()->admin()->create();
        $dpp = User::factory()->dpp()->create();

        $this->actingAs($admin)
            ->get(route('log-kegiatan.export-student-pdf'))
            ->assertForbidden();

        $this->actingAs($dpp)
            ->get(route('log-kegiatan.export-student-pdf'))
            ->assertForbidden();
    }

    public function test_student_without_participant_profile_cannot_download_pdf(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('log-kegiatan.export-student-pdf'))
            ->assertNotFound();
    }

    public function test_python_service_generates_a_valid_pdf(): void
    {
        $service = app(PythonAnalyticsService::class);

        $pdf = $service->generateStudentActivityPdf([
            'mahasiswa' => [
                'nama' => 'Padlan',
                'nim' => '152023001',
                'prodi' => 'Informatika',
            ],
            'kegiatan' => [],
        ]);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringEndsWith('%%EOF', rtrim($pdf));
    }

    public function test_guest_cannot_download_student_pdf(): void
    {
        $this->get(route('log-kegiatan.export-student-pdf'))
            ->assertRedirect(route('login'));
    }
}