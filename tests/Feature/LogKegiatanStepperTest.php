<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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

        $log = LogKegiatan::first();
        $response->assertRedirect(route('log-kegiatan.edit', ['log_kegiatan' => $log->id, 'step' => 2]));
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

        $response->assertRedirect(route('log-kegiatan.edit', ['log_kegiatan' => $log->id, 'step' => 3]));
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
            'step' => 2,
            'tanggal_kehadiran' => '2026-11-02',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $response->assertRedirect(route('log-kegiatan.edit', ['log_kegiatan' => $log->id, 'step' => 3]));
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'ditolak',
            'tanggal_kehadiran' => '2026-11-02',
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
            'step' => 2,
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

    public function test_log_kegiatan_cannot_be_deleted(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);
        $log = LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
        ]);

        $response = $this->actingAs($user)->delete('/log-kegiatan/'.$log->id);

        $response->assertMethodNotAllowed();
        $this->assertDatabaseHas('log_kegiatans', ['id' => $log->id]);
    }

    public function test_mahasiswa_can_save_7_step_stepper_flow(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id]);

        // Step 1: Save identity
        $response1 = $this->actingAs($user)->post(route('log-kegiatan.save-step.create'), [
            'step' => 1,
            'kode_prodi' => $peserta->kode_prodi,
            'nama_lengkap' => $peserta->nama_lengkap,
        ]);

        $log = LogKegiatan::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(2, $log->current_step);
        $this->assertEquals('draft', $log->status);

        // Step 2: Save schedule
        $this->actingAs($user)->post(route('log-kegiatan.save-step', $log), [
            'step' => 2,
            'tanggal_kehadiran' => '2026-02-18',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $log->refresh();
        $this->assertEquals('2026-02-18', $log->tanggal_kehadiran->format('Y-m-d'));
        $this->assertEquals('08:00', $log->jam_mulai);
        $this->assertEquals('12:00', $log->jam_selesai);
        $this->assertEquals('08:00 - 12:00', $log->jam_kehadiran);
        $this->assertEquals(3, $log->current_step);
    }

    public function test_stepper_saves_multiple_photos_and_deliverable_files(): void
    {
        Storage::fake('public');

        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $log = LogKegiatan::factory()->draft(4)->create(['peserta_id' => $peserta->id]);

        $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 5,
            'foto_kegiatan' => [
                UploadedFile::fake()->image('foto-satu.jpg'),
                UploadedFile::fake()->image('foto-dua.jpg'),
            ],
        ])->assertRedirect(route('log-kegiatan.edit', ['log_kegiatan' => $log->id, 'step' => 6]));

        $this->actingAs($student)->post(route('log-kegiatan.save-step', $log), [
            'step' => 6,
            'deskripsi_luaran' => 'Dokumentasi hasil kegiatan bersama warga desa.',
            'file_luaran' => [
                UploadedFile::fake()->create('luaran-satu.pdf', 500, 'application/pdf'),
                UploadedFile::fake()->create('luaran-dua.pdf', 500, 'application/pdf'),
            ],
        ])->assertRedirect(route('log-kegiatan.edit', ['log_kegiatan' => $log->id, 'step' => 7]));

        $this->assertSame(2, $log->fotoKegiatanFiles()->count());
        $this->assertSame(2, $log->fileLuaranFiles()->count());
        foreach ($log->files as $file) {
            Storage::disk('public')->assertExists($file->file_path);
        }
    }

    public function test_participant_can_set_prodi_before_starting_activity_logs(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $prodi = Prodi::factory()->create();
        $peserta = Peserta::factory()->create(['user_id' => $user->id, 'kode_prodi' => null]);

        $response = $this->actingAs($user)->post(route('log-kegiatan.save-step.create'), [
            'step' => 1,
            'kode_prodi' => $prodi->kode_prodi,
            'nama_lengkap' => 'Nama yang dimanipulasi',
            'email' => 'palsu@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pesertas', [
            'id' => $peserta->id,
            'kode_prodi' => $prodi->kode_prodi,
            'nama_lengkap' => $peserta->nama_lengkap,
        ]);
        $this->assertDatabaseMissing('pesertas', [
            'id' => $peserta->id,
            'nama_lengkap' => 'Nama yang dimanipulasi',
        ]);
    }

    public function test_student_cannot_select_master_activity_from_another_prodi(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $otherProdi = Prodi::factory()->create();
        $masterKegiatan = MasterKegiatan::factory()->create(['kode_prodi' => $otherProdi->kode_prodi]);

        $response = $this->actingAs($student)->post(route('log-kegiatan.save-step.create'), [
            'step' => 3,
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
            'step' => 7,
            'action' => 'submit',
        ]);

        $response->assertRedirect(route('log-kegiatan.index'));
        $response->assertSessionHas('success', 'Laporan kegiatan KKN berhasil disubmit!');
        $this->assertDatabaseHas('log_kegiatans', [
            'id' => $log->id,
            'status' => 'menunggu',
            'current_step' => 7,
        ]);
    }
}
