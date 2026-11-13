<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProgressAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_progress_analysis_covers_all_programs_and_approved_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $informatics = Prodi::factory()->create(['nama_prodi' => 'Informatika']);
        $design = Prodi::factory()->create(['nama_prodi' => 'Desain']);
        $activeInformaticsStudent = Peserta::factory()->create(['kode_prodi' => $informatics->kode_prodi]);
        Peserta::factory()->create(['kode_prodi' => $informatics->kode_prodi]);
        $designStudent = Peserta::factory()->create(['kode_prodi' => $design->kode_prodi]);
        $educationActivity = MasterKegiatan::factory()->create([
            'kode_prodi' => $informatics->kode_prodi,
            'nama_kegiatan' => 'Pelatihan Pengolahan Data',
            'kategori' => 'Pendidikan',
        ]);
        $environmentActivity = MasterKegiatan::factory()->create([
            'kode_prodi' => $design->kode_prodi,
            'nama_kegiatan' => 'Pemetaan Lingkungan',
            'kategori' => 'Lingkungan',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $activeInformaticsStudent->id,
            'master_kegiatan_id' => $educationActivity->id,
            'status' => 'disetujui',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:30',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $designStudent->id,
            'master_kegiatan_id' => $environmentActivity->id,
            'status' => 'disetujui',
            'jam_mulai' => '09:00',
            'jam_selesai' => '12:00',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $activeInformaticsStudent->id,
            'master_kegiatan_id' => $educationActivity->id,
            'status' => 'disetujui',
            'jam_mulai' => '14:00',
            'jam_selesai' => '15:30',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $activeInformaticsStudent->id,
            'master_kegiatan_id' => $educationActivity->id,
            'status' => 'menunggu',
            'jam_mulai' => '12:00',
            'jam_selesai' => '14:00',
        ]);

        $this->actingAs($admin)
            ->get(route('progress-analysis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgressAnalysis')
                ->where('isDpp', false)
                ->where('analysis.summary.total_mahasiswa', 3)
                ->where('analysis.summary.mahasiswa_aktif', 2)
                ->where('analysis.summary.total_kegiatan', 3)
                ->where('analysis.summary.total_jam', 7)
                ->has('analysis.per_prodi', 2)
                ->has('analysis.per_kegiatan', 2)
                ->where('analysis.per_kegiatan.0.nama_kegiatan', 'Pelatihan Pengolahan Data')
                ->where('analysis.per_kegiatan.0.total_kegiatan', 2)
                ->where('analysis.per_kegiatan.0.total_jam', 4)
            );

        $this->actingAs($admin)
            ->get(route('progress-analysis.index', ['kode_prodi' => $informatics->kode_prodi]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedKodeProdi', $informatics->kode_prodi)
                ->where('selectedNamaProdi', 'Informatika')
                ->where('analysis.summary.total_mahasiswa', 2)
                ->where('analysis.summary.total_kegiatan', 2)
                ->has('analysis.per_prodi', 1)
                ->has('analysis.per_kegiatan', 1)
            );
    }

    public function test_dpp_progress_analysis_only_includes_its_program(): void
    {
        $prodi = Prodi::factory()->create(['nama_prodi' => 'Informatika']);
        $otherProdi = Prodi::factory()->create(['nama_prodi' => 'Desain']);
        $dpp = User::factory()->dpp()->create();
        $dpp->dppProfile()->create(['kode_prodi' => $prodi->kode_prodi]);
        $peserta = Peserta::factory()->create(['kode_prodi' => $prodi->kode_prodi]);
        $outsideProdi = Peserta::factory()->create(['kode_prodi' => $otherProdi->kode_prodi]);
        $masterKegiatan = MasterKegiatan::factory()->create([
            'kode_prodi' => $prodi->kode_prodi,
            'kategori' => 'Pendidikan',
        ]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'master_kegiatan_id' => $masterKegiatan->id,
            'status' => 'disetujui',
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:00',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $outsideProdi->id,
            'status' => 'disetujui',
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
        ]);

        $this->actingAs($dpp)
            ->get(route('progress-analysis.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ProgressAnalysis')
                ->where('isDpp', true)
                ->where('dppProdi', 'Informatika')
                ->where('analysis.summary.total_mahasiswa', 1)
                ->where('analysis.summary.total_kegiatan', 1)
                ->where('analysis.summary.total_jam', 3)
                ->has('analysis.per_prodi', 1)
                ->has('analysis.per_kegiatan', 1)
            );
    }

    public function test_student_cannot_open_progress_analysis(): void
    {
        $student = User::factory()->mahasiswa()->create();

        $this->actingAs($student)
            ->get(route('progress-analysis.index'))
            ->assertForbidden();
    }
}
