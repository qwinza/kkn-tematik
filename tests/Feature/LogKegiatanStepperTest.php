<?php

namespace Tests\Feature;

use App\Exports\ApprovedLogKegiatanExport;
use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\RencanaKehadiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogKegiatanStepperTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_only_sees_their_own_logs(): void
    {
        $user1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::factory()->create(['user_id' => $user1->id]);
        LogKegiatan::factory()->count(2)->create(['peserta_id' => $peserta1->id]);

        $user2 = User::factory()->mahasiswa()->create();
        $peserta2 = Peserta::factory()->create(['user_id' => $user2->id]);
        LogKegiatan::factory()->count(3)->create(['peserta_id' => $peserta2->id]);

        $response = $this->actingAs($user1)->get(route('log-kegiatan.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LogKegiatan/Index')
            ->has('logs.data', 2)
            ->where('isAdmin', false)
        );
    }

    public function test_admin_can_view_all_student_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $peserta1 = Peserta::factory()->create();
        LogKegiatan::factory()->count(2)->create(['peserta_id' => $peserta1->id]);

        $peserta2 = Peserta::factory()->create();
        LogKegiatan::factory()->count(3)->create(['peserta_id' => $peserta2->id]);

        $response = $this->actingAs($admin)->get(route('log-kegiatan.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('LogKegiatan/Index')
            ->has('logs.data', 5)
            ->where('isAdmin', true)
        );
    }

    public function test_admin_log_list_only_includes_reviewable_statuses_and_sorts_them(): void
    {
        $admin = User::factory()->admin()->create();
        $draft = LogKegiatan::factory()->draft()->create();
        $waiting = LogKegiatan::factory()->create(['status' => 'menunggu']);
        $approved = LogKegiatan::factory()->create(['status' => 'disetujui']);
        $rejected = LogKegiatan::factory()->create(['status' => 'ditolak']);

        $response = $this->actingAs($admin)->get(route('log-kegiatan.index', [
            'sort' => 'status',
            'direction' => 'asc',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('LogKegiatan/Index')
            ->has('logs.data', 3)
            ->where('logs.data.0.id', $approved->id)
            ->where('logs.data.1.id', $rejected->id)
            ->where('logs.data.2.id', $waiting->id)
        );

        $this->assertDatabaseHas('log_kegiatans', ['id' => $draft->id, 'status' => 'draft']);
    }

    public function test_admin_cannot_filter_log_list_by_draft_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('log-kegiatan.index', ['status' => 'draft']))
            ->assertSessionHasErrors('status');
    }

    public function test_mahasiswa_can_initiate_step_1_saving_as_draft(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $prodi = Prodi::factory()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id, 'kode_prodi' => $prodi->kode_prodi]);
        $master = MasterKegiatan::factory()->create(['kode_prodi' => $prodi->kode_prodi]);

        $response = $this->actingAs($user)->post(route('log-kegiatan.step-1'), [
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-01',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'lokasi_kegiatan' => 'Lapangan',
            'latitude' => -6.89750000,
            'longitude' => 107.63580000,
        ]);

        $this->assertDatabaseHas('log_kegiatans', [
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $master->id,
            'tanggal_kehadiran' => '2026-11-01',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'lokasi_kegiatan' => 'Lapangan',
            'current_step' => 2,
            'status' => 'draft',
        ]);

        $log = LogKegiatan::query()->firstOrFail();
        $this->assertRedirectsToEncryptedLogEdit($response, $log, 2);
    }

    public function test_activity_form_only_lists_activities_from_participant_prodi(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $prodi = Prodi::factory()->create();
        Peserta::factory()->create(['user_id' => $user->id, 'kode_prodi' => $prodi->kode_prodi]);
        $matchingActivity = MasterKegiatan::factory()->create(['kode_prodi' => $prodi->kode_prodi]);
        $otherProdi = Prodi::factory()->create();
        MasterKegiatan::factory()->create(['kode_prodi' => $otherProdi->kode_prodi]);

        $response = $this->actingAs($user)->get(route('log-kegiatan.create'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('LogKegiatan/Stepper')
            ->has('masterKegiatans', 1)
            ->where('masterKegiatans.0.id', $matchingActivity->id)
        );
    }

    public function test_mahasiswa_can_save_step_2_with_photo_upload(): void
    {
        Storage::fake('public');

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft(1)->create(['peserta_id' => $peserta->id]);

        $photos = [
            UploadedFile::fake()->image('dokumentasi-satu.jpg'),
            UploadedFile::fake()->image('dokumentasi-dua.jpg'),
        ];

        $response = $this->actingAs($user)->post(route('log-kegiatan.step-2', $log), [
            'deskripsi_kegiatan' => 'Melakukan pembersihan saluran air dan penanaman bibit pohon di desa binaan.',
            'foto_kegiatan' => $photos,
        ]);

        $log->refresh();

        $this->assertEquals('Melakukan pembersihan saluran air dan penanaman bibit pohon di desa binaan.', $log->deskripsi_kegiatan);
        $this->assertSame(2, $log->fotoKegiatanFiles()->count());
        foreach ($log->fotoKegiatanFiles as $photo) {
            Storage::disk('public')->assertExists($photo->file_path);
        }
        $this->assertEquals(3, $log->current_step);

        $this->assertRedirectsToEncryptedLogEdit($response, $log, 3);
    }

    public function test_mahasiswa_can_save_step_3_as_draft(): void
    {
        Storage::fake('public');

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft(2)->create(['peserta_id' => $peserta->id]);

        $file = UploadedFile::fake()->create('laporan_kegiatan.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user)->post(route('log-kegiatan.step-3', $log), [
            'deskripsi_luaran' => 'Modul panduan pemilahan sampah warga RW 05.',
            'file_luaran' => [
                $file,
                UploadedFile::fake()->create('lampiran-tambahan.pdf', 512, 'application/pdf'),
            ],
            'submit_action' => 'draft',
        ]);

        $log->refresh();

        $this->assertEquals('Modul panduan pemilahan sampah warga RW 05.', $log->deskripsi_luaran);
        $this->assertSame(2, $log->fileLuaranFiles()->count());
        foreach ($log->fileLuaranFiles as $outputFile) {
            Storage::disk('public')->assertExists($outputFile->file_path);
        }
        $this->assertEquals('draft', $log->status);

        $response->assertRedirect(route('log-kegiatan.index'));
        $response->assertSessionHas('success');
    }

    public function test_mahasiswa_submission_waits_for_admin_approval(): void
    {
        Storage::fake('public');

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft(2)->create([
            'peserta_id' => $peserta->id,
            'deskripsi_kegiatan' => 'Sosialisasi bank sampah ke masyarakat.',
        ]);
        $photoPath = UploadedFile::fake()->image('foto-kegiatan.jpg')->store('kegiatan/foto', 'public');
        $log->files()->create([
            'jenis_file' => 'foto_kegiatan',
            'file_path' => $photoPath,
            'nama_file_asli' => 'foto-kegiatan.jpg',
            'ukuran_file' => 100,
        ]);

        $file = UploadedFile::fake()->create('luaran_final.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($user)->post(route('log-kegiatan.step-3', $log), [
            'deskripsi_luaran' => 'Dokumen hasil evaluasi dan survei kepuasan masyarakat.',
            'file_luaran' => [$file],
            'submit_action' => 'submit',
        ]);

        $log->refresh();

        $this->assertEquals('menunggu', $log->status);
        $this->assertEquals(3, $log->current_step);
        $response->assertRedirect(route('log-kegiatan.index'));
    }

    public function test_admin_can_approve_a_waiting_log_kegiatan(): void
    {
        $admin = User::factory()->admin()->create();
        $log = LogKegiatan::factory()->create(['status' => 'menunggu']);

        $response = $this->actingAs($admin)->post(route('log-kegiatan.approve', $log));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Laporan kegiatan berhasil disetujui.');
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'disetujui',
        ]);
    }

    public function test_admin_cannot_approve_a_draft_log_kegiatan(): void
    {
        $admin = User::factory()->admin()->create();
        $log = LogKegiatan::factory()->draft()->create();

        $response = $this->actingAs($admin)->post(route('log-kegiatan.approve', $log));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Hanya laporan berstatus menunggu yang dapat disetujui.');
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_reject_a_waiting_log_kegiatan(): void
    {
        $admin = User::factory()->admin()->create();
        $log = LogKegiatan::factory()->create(['status' => 'menunggu']);

        $response = $this->actingAs($admin)->post(route('log-kegiatan.reject', $log));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Laporan kegiatan ditolak dan dapat diperbaiki oleh mahasiswa.');
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'ditolak',
        ]);
    }

    public function test_student_can_resume_a_rejected_log(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'ditolak',
        ]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 1,
            'tanggal_kehadiran' => '2026-11-02',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $this->assertRedirectsToEncryptedLogEdit($response, $log, 2);
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'ditolak',
            'tanggal_kehadiran' => '2026-11-02',
        ]);
    }

    public function test_student_can_attach_their_attendance_plan_to_a_log(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);
        $rencana = RencanaKehadiran::create([
            'peserta_id' => $peserta->id,
            'tanggal_rencana' => '2026-11-12',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $response = $this->actingAs($user)->post(route('log-kegiatan.save-step', $log), [
            'step' => 1,
            'rencana_kehadiran_id' => $rencana->id,
        ]);

        $this->assertRedirectsToEncryptedLogEdit($response, $log, 2);
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'rencana_kehadiran_id' => $rencana->id,
            'tanggal_kehadiran' => '2026-11-12',
            'jam_mulai' => '10:00',
            'jam_selesai' => '13:00',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);
    }

    public function test_student_cannot_attach_another_students_attendance_plan(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);

        $otherStudent = User::factory()->mahasiswa()->create();
        $otherPeserta = Peserta::factory()->create(['user_id' => $otherStudent->id]);
        $otherRencana = RencanaKehadiran::create([
            'peserta_id' => $otherPeserta->id,
            'tanggal_rencana' => '2026-11-12',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $this->actingAs($student)
            ->post(route('log-kegiatan.save-step', $log), [
                'step' => 1,
                'rencana_kehadiran_id' => $otherRencana->id,
            ])
            ->assertSessionHasErrors('rencana_kehadiran_id');

        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'rencana_kehadiran_id' => null,
        ]);
    }

    public function test_mahasiswa_cannot_approve_a_log_kegiatan(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'menunggu',
        ]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.approve', $log));

        $response->assertForbidden();
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_student_cannot_update_a_waiting_log_kegiatan(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'menunggu',
        ]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 1,
            'tanggal_kehadiran' => '2026-02-18',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'menunggu',
            'tanggal_kehadiran' => $log->tanggal_kehadiran->format('Y-m-d'),
        ]);
    }

    public function test_cannot_upload_file_larger_than_50mb(): void
    {
        Storage::fake('public');

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft(2)->create(['peserta_id' => $peserta->id]);

        // 52MB file (> 50MB / 51200 KB)
        $oversizedFile = UploadedFile::fake()->create('oversized.pdf', 53000, 'application/pdf');

        $response = $this->actingAs($user)->post(route('log-kegiatan.step-3', $log), [
            'deskripsi_luaran' => 'Luaran gagal karena melebihi batas 50MB',
            'file_luaran' => $oversizedFile,
            'submit_action' => 'draft',
        ]);

        $response->assertSessionHasErrors('file_luaran');
    }

    public function test_mahasiswa_cannot_edit_other_mahasiswa_log(): void
    {
        $user1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::factory()->create(['user_id' => $user1->id]);

        $user2 = User::factory()->mahasiswa()->create();
        $peserta2 = Peserta::factory()->create(['user_id' => $user2->id]);

        $log = LogKegiatan::factory()->create(['peserta_id' => $peserta1->id]);

        $response = $this->actingAs($user2)->get(route('log-kegiatan.edit', $log));
        $response->assertForbidden();

        $responseStep1 = $this->actingAs($user2)->post(route('log-kegiatan.step-1.update', $log), [
            'master_kegiatan_id' => $log->master_kegiatan_id,
            'tanggal_kehadiran' => '2026-11-01',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'lokasi_kegiatan' => 'Lapangan',
        ]);
        $responseStep1->assertForbidden();
    }

    public function test_student_can_delete_their_draft_and_its_uploaded_files(): void
    {
        Storage::fake('public');

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);
        $filePath = UploadedFile::fake()->image('draft.jpg')->store('kegiatan/foto', 'public');
        $log->files()->create([
            'jenis_file' => 'foto_kegiatan',
            'file_path' => $filePath,
            'nama_file_asli' => 'draft.jpg',
            'ukuran_file' => 100,
        ]);

        $response = $this->actingAs($user)->delete(route('log-kegiatan.destroy', $log));

        $response->assertRedirect(route('log-kegiatan.index'));
        $response->assertSessionHas('success', 'Draft kegiatan berhasil dihapus.');
        $this->assertDatabaseMissing('log_kegiatans', ['id' => $log->id]);
        $this->assertDatabaseMissing('log_kegiatan_files', ['log_kegiatan_id' => $log->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_student_cannot_delete_another_students_draft(): void
    {
        $owner = User::factory()->mahasiswa()->create();
        $ownerPeserta = Peserta::factory()->create(['user_id' => $owner->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $ownerPeserta->id]);
        $otherStudent = User::factory()->mahasiswa()->create();
        Peserta::factory()->create(['user_id' => $otherStudent->id]);

        $this->actingAs($otherStudent)
            ->delete(route('log-kegiatan.destroy', $log))
            ->assertForbidden();

        $this->assertModelExists($log);
    }

    public function test_student_cannot_delete_a_log_that_is_not_a_draft(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->create(['peserta_id' => $peserta->id]);

        $this->actingAs($user)
            ->delete(route('log-kegiatan.destroy', $log))
            ->assertForbidden();

        $this->assertModelExists($log);
    }

    public function test_mahasiswa_can_save_stepper_schedule_and_activity_flow(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $prodi = Prodi::factory()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id, 'kode_prodi' => $prodi->kode_prodi]);
        $master = MasterKegiatan::factory()->create(['kode_prodi' => $prodi->kode_prodi]);

        // Step 1: Save schedule
        $this->actingAs($user)->post(route('log-kegiatan.save-step.create'), [
            'step' => 1,
            'tanggal_kehadiran' => '2026-02-18',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $log = LogKegiatan::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('2026-02-18', $log->tanggal_kehadiran->format('Y-m-d'));
        $this->assertEquals('08:00', $log->jam_mulai);
        $this->assertEquals('12:00', $log->jam_selesai);
        $this->assertEquals('08:00 - 12:00', $log->jam_kehadiran);
        $this->assertEquals(2, $log->current_step);
        $this->assertEquals('draft', $log->status);

        // Step 2: Save activity
        $this->actingAs($user)->post(route('log-kegiatan.save-step', $log), [
            'step' => 2,
            'master_kegiatan_id' => $master->id,
        ]);

        $log->refresh();
        $this->assertEquals($master->id, $log->master_kegiatan_id);
        $this->assertEquals(3, $log->current_step);
    }

    public function test_stepper_saves_multiple_photos_and_deliverable_files(): void
    {
        Storage::fake('public');

        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->draft(4)->create(['peserta_id' => $peserta->id]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 4,
            'foto_kegiatan' => [
                UploadedFile::fake()->image('foto-satu.jpg'),
                UploadedFile::fake()->image('foto-dua.jpg'),
            ],
        ]);
        $this->assertRedirectsToEncryptedLogEdit($response, $log, 5);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 5,
            'deskripsi_luaran' => 'Dokumentasi hasil kegiatan bersama warga desa.',
            'file_luaran' => [
                UploadedFile::fake()->create('luaran-satu.pdf', 500, 'application/pdf'),
                UploadedFile::fake()->create('luaran-dua.pdf', 500, 'application/pdf'),
            ],
        ]);
        $this->assertRedirectsToEncryptedLogEdit($response, $log, 6);

        $this->assertSame(2, $log->fotoKegiatanFiles()->count());
        $this->assertSame(2, $log->fileLuaranFiles()->count());
        foreach ($log->files as $file) {
            Storage::disk('public')->assertExists($file->file_path);
        }
    }

    public function test_student_cannot_select_master_activity_from_another_prodi(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $otherProdi = Prodi::factory()->create();
        $masterKegiatan = MasterKegiatan::factory()->create(['kode_prodi' => $otherProdi->kode_prodi]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step.create'), [
            'step' => 2,
            'master_kegiatan_id' => $masterKegiatan->id,
        ]);

        $response->assertSessionHasErrors('master_kegiatan_id');
        $this->assertDatabaseMissing('log_kegiatans', [
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $masterKegiatan->id,
        ]);
    }

    public function test_final_step_submission_enters_admin_approval_queue(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $masterKegiatan = MasterKegiatan::factory()->create(['kode_prodi' => $peserta->kode_prodi]);
        $log = LogKegiatan::factory()->draft(6)->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $masterKegiatan->id,
            'tanggal_kehadiran' => '2026-02-18',
            'lokasi_kegiatan' => 'Lapangan',
            'deskripsi_kegiatan' => 'Membersihkan saluran air bersama warga desa.',
            'deskripsi_luaran' => 'Dokumentasi kegiatan pembersihan saluran.',
        ]);
        $log->files()->createMany([
            [
                'jenis_file' => 'foto_kegiatan',
                'file_path' => 'kegiatan/foto/dokumentasi.jpg',
                'nama_file_asli' => 'dokumentasi.jpg',
                'ukuran_file' => 100,
            ],
            [
                'jenis_file' => 'file_luaran',
                'file_path' => 'kegiatan/luaran/laporan.pdf',
                'nama_file_asli' => 'laporan.pdf',
                'ukuran_file' => 100,
            ],
        ]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 6,
            'action' => 'submit',
        ]);

        $response->assertRedirect(route('log-kegiatan.index'));
        $response->assertSessionHas('success', 'Laporan kegiatan KKN berhasil disubmit!');
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'menunggu',
            'current_step' => 6,
        ]);
    }

    public function test_log_details_and_stepper_use_encrypted_route_keys(): void
    {
        Storage::fake('public');

        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->draft(4)->create(['peserta_id' => $peserta->id]);
        $file = $log->files()->create([
            'jenis_file' => 'file_luaran',
            'file_path' => 'kegiatan/luaran/laporan.pdf',
            'nama_file_asli' => 'laporan.pdf',
            'ukuran_file' => 7,
        ]);
        Storage::disk('public')->put($file->file_path, 'content');
        $admin = User::factory()->admin()->create();
        $editUrl = route('log-kegiatan.edit', ['log_kegiatan' => $log, 'step' => 4]);
        $showUrl = route('log-kegiatan.show', $log);
        $fileUrl = $file->url;

        $this->assertSame(0, preg_match('#/log-kegiatan/\\d+(?:/|$)#', (string) parse_url($editUrl, PHP_URL_PATH)));
        $this->assertSame(0, preg_match('#/log-kegiatan/\\d+(?:/|$)#', (string) parse_url($showUrl, PHP_URL_PATH)));
        $this->assertSame(0, preg_match('#/log-kegiatan/\\d+/files/\\d+$#', (string) parse_url($fileUrl, PHP_URL_PATH)));

        $this->actingAs($student)
            ->get($editUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LogKegiatan/Stepper')
                ->where('log.id', $log->id)
                ->has('log.route_key')
            );

        $this->actingAs($admin)
            ->get($showUrl)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LogKegiatan/Show')
                ->where('log.id', $log->id)
                ->has('log.route_key')
            );

        $this->actingAs($admin)->get($fileUrl)->assertOk();
    }

    public function test_plain_numeric_log_route_key_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);

        $this->actingAs($admin)
            ->get('/log-kegiatan/'.$log->id)
            ->assertNotFound();
    }

    public function test_admin_can_filter_logs_by_date_and_attendance_session(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingLog = LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-08',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $this->actingAs($admin)
            ->get(route('log-kegiatan.index', [
                'tanggal' => '2026-11-07',
                'sesi' => '07:00 - 10:00 (Pagi)',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.id', $matchingLog->id)
                ->where('filters.tanggal', '2026-11-07')
                ->where('filters.sesi', '07:00 - 10:00 (Pagi)')
                ->where('pendingCount', 1)
            );
    }

    public function test_admin_can_approve_all_matching_waiting_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $matchingLog = LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $otherDateLog = LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-08',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $otherSessionLog = LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $this->actingAs($admin)
            ->post(route('log-kegiatan.approve-all'), [
                'tanggal' => '2026-11-07',
                'sesi' => '07:00 - 10:00 (Pagi)',
            ])
            ->assertSessionHas('success', '1 laporan kegiatan berhasil disetujui.');

        $this->assertDatabaseHas('log_kegiatans', ['id' => $matchingLog->id, 'status' => 'disetujui']);
        $this->assertDatabaseHas('log_kegiatans', ['id' => $otherDateLog->id, 'status' => 'menunggu']);
        $this->assertDatabaseHas('log_kegiatans', ['id' => $otherSessionLog->id, 'status' => 'menunggu']);
    }

    public function test_admin_can_filter_logs_by_program_study(): void
    {
        $admin = User::factory()->admin()->create();
        $selectedProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();
        $selectedUser = User::factory()->mahasiswa()->create();
        $selectedPeserta = Peserta::factory()->create([
            'user_id' => $selectedUser->id,
            'kode_prodi' => $selectedProdi->kode_prodi,
        ]);
        $otherUser = User::factory()->mahasiswa()->create();
        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherUser->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);
        $matchingLog = LogKegiatan::factory()->create([
            'peserta_id' => $selectedPeserta->id,
            'status' => 'menunggu',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($admin)
            ->get(route('log-kegiatan.index', ['kode_prodi' => $selectedProdi->kode_prodi]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.id', $matchingLog->id)
                ->where('filters.kode_prodi', $selectedProdi->kode_prodi)
                ->has('prodis')
            );
    }

    public function test_admin_export_contains_only_approved_logs_matching_filters(): void
    {
        Excel::fake();

        $admin = User::factory()->admin()->create();
        $selectedProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();
        $selectedUser = User::factory()->mahasiswa()->create();
        $selectedPeserta = Peserta::factory()->create([
            'user_id' => $selectedUser->id,
            'kode_prodi' => $selectedProdi->kode_prodi,
        ]);
        $otherUser = User::factory()->mahasiswa()->create();
        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherUser->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);
        $matchingLog = LogKegiatan::factory()->create([
            'peserta_id' => $selectedPeserta->id,
            'status' => 'disetujui',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'deskripsi_kegiatan' => 'Laporan untuk export',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'disetujui',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'deskripsi_kegiatan' => 'Laporan prodi lain',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $selectedPeserta->id,
            'status' => 'disetujui',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
            'deskripsi_kegiatan' => 'Laporan sesi lain',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $selectedPeserta->id,
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
            'deskripsi_kegiatan' => 'Laporan belum disetujui',
        ]);

        $fileName = 'laporan-kegiatan-disetujui-'.now()->format('Ymd-His').'.xlsx';

        $this->actingAs($admin)
            ->get(route('log-kegiatan.export', [
                'status' => 'disetujui',
                'kode_prodi' => $selectedProdi->kode_prodi,
                'tanggal' => '2026-11-07',
                'sesi' => '07:00 - 10:00 (Pagi)',
            ]))
            ->assertOk();

        Excel::assertDownloaded($fileName, function (ApprovedLogKegiatanExport $export) use ($matchingLog): bool {
            return $export->query()->pluck('id')->all() === [$matchingLog->id]
                && $export->headings()[1] === 'Tanggal Kehadiran';
        });
    }

    public function test_export_is_empty_when_active_status_is_not_approved(): void
    {
        Excel::fake();

        $admin = User::factory()->admin()->create();
        LogKegiatan::factory()->create([
            'status' => 'menunggu',
            'tanggal_kehadiran' => '2026-11-07',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $fileName = 'laporan-kegiatan-disetujui-'.now()->format('Ymd-His').'.xlsx';

        $this->actingAs($admin)
            ->get(route('log-kegiatan.export', ['status' => 'menunggu']))
            ->assertOk();

        Excel::assertDownloaded($fileName, function (ApprovedLogKegiatanExport $export): bool {
            return $export->query()->count() === 0;
        });
    }

    public function test_student_cannot_use_admin_bulk_approval_or_export(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->post(route('log-kegiatan.approve-all'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('log-kegiatan.export'))
            ->assertForbidden();
    }

    public function test_mahasiswa_can_save_rincian_step_with_keterangan_tempat(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);

        $response = $this->actingAs($user)->post(route('log-kegiatan.save-step', $log), [
            'step' => 3,
            'lokasi_kegiatan' => 'Lapangan',
            'keterangan_tempat' => 'Balai RW 05',
            'deskripsi_kegiatan' => 'Melakukan pendataan sampah bersama warga desa.',
            'latitude' => -6.89750000,
            'longitude' => 107.63580000,
        ]);

        $this->assertRedirectsToEncryptedLogEdit($response, $log, 4);

        $log->refresh();
        $this->assertEquals('Balai RW 05', $log->keterangan_tempat);
        $this->assertEquals('Lapangan', $log->lokasi_kegiatan);
        $this->assertEquals('Melakukan pendataan sampah bersama warga desa.', $log->deskripsi_kegiatan);
        $this->assertEquals(4, $log->current_step);
    }

    public function test_rincian_step_requires_keterangan_tempat(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);

        $this->actingAs($user)
            ->post(route('log-kegiatan.save-step', $log), [
                'step' => 3,
                'lokasi_kegiatan' => 'Lapangan',
                'deskripsi_kegiatan' => 'Deskripsi tanpa keterangan tempat.',
            ])
            ->assertSessionHasErrors('keterangan_tempat');
    }

    private function assertRedirectsToEncryptedLogEdit(TestResponse $response, LogKegiatan $log, int $step): void
    {
        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $path = (string) parse_url($location, PHP_URL_PATH);
        $matches = [];

        $this->assertSame(1, preg_match('#\A/log-kegiatan/([A-Za-z0-9_-]+)/edit\z#', $path, $matches));
        $encryptedKey = strtr($matches[1], '-_', '+/');
        $encryptedKey .= str_repeat('=', (4 - strlen($encryptedKey) % 4) % 4);

        $this->assertSame((string) $log->getKey(), Crypt::decryptString($encryptedKey));
        $this->assertSame("step={$step}", parse_url($location, PHP_URL_QUERY));
    }
    public function test_dpp_only_sees_logs_from_own_prodi(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $ownStudent = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();

        $ownPeserta = Peserta::factory()->create([
            'user_id' => $ownStudent->id,
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherStudent->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $ownLog = LogKegiatan::factory()->create([
            'peserta_id' => $ownPeserta->id,
            'status' => 'menunggu',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($dpp)
            ->get(route('log-kegiatan.index', [
                'kode_prodi' => $otherProdi->kode_prodi,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LogKegiatan/Index')
                ->has('logs.data', 1)
                ->where('logs.data.0.id', $ownLog->id)
                ->where('isDpp', true)
                ->where('isReviewer', true)
                ->where('pendingCount', 1)
            );
    }

    public function test_dpp_can_approve_own_log_but_cannot_approve_other_prodi_log(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $ownStudent = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();

        $ownPeserta = Peserta::factory()->create([
            'user_id' => $ownStudent->id,
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherStudent->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $ownLog = LogKegiatan::factory()->create([
            'peserta_id' => $ownPeserta->id,
            'status' => 'menunggu',
        ]);

        $otherLog = LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($dpp)
            ->post(route('log-kegiatan.approve', $ownLog))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $ownLog->id,
            'status' => 'disetujui',
        ]);

        $this->post(route('log-kegiatan.approve', $otherLog))
            ->assertForbidden();

        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $otherLog->id,
            'status' => 'menunggu',
        ]);
    }

    public function test_dpp_cannot_access_another_prodis_log_detail(): void
    {
        $ownProdi = Prodi::factory()->create();
        $otherProdi = Prodi::factory()->create();

        $dpp = User::factory()->dpp()->create();

        $dpp->dppProfile()->create([
            'kode_prodi' => $ownProdi->kode_prodi,
        ]);

        $otherStudent = User::factory()->mahasiswa()->create();

        $otherPeserta = Peserta::factory()->create([
            'user_id' => $otherStudent->id,
            'kode_prodi' => $otherProdi->kode_prodi,
        ]);

        $otherLog = LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'menunggu',
        ]);

        $this->actingAs($dpp)
            ->get(route('log-kegiatan.show', $otherLog))
            ->assertForbidden();
    }
}
