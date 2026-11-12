<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_summarizes_all_activity_logs(): void
    {
        $admin = User::factory()->admin()->create();
        LogKegiatan::factory()->create(['status' => 'disetujui']);
        LogKegiatan::factory()->create(['status' => 'menunggu']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalKegiatan', 2)
                ->where('summary.totalDisetujui', 1)
            );
    }

    public function test_mahasiswa_dashboard_summarizes_only_own_activity_logs(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $mahasiswa->id]);
        $otherPeserta = Peserta::factory()->create();

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'disetujui',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'menunggu',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $otherPeserta->id,
            'status' => 'disetujui',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalKegiatan', 2)
                ->where('summary.totalDisetujui', 1)
            );
    }

    public function test_dashboard_summarizes_activities_by_location(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $mahasiswa->id]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'lokasi_kegiatan' => 'Lapangan',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'lokasi_kegiatan' => 'Lapangan',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'lokasi_kegiatan' => 'Kampus ITENAS',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalKegiatan', 3)
                ->where('summary.totalLapangan', 2)
                ->where('summary.totalKampus', 1)
            );
    }

    public function test_dashboard_sums_hours_for_approved_activities_only(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $mahasiswa->id]);

        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'disetujui',
            'jam_mulai' => '08:00',
            'jam_selesai' => '11:30',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'disetujui',
            'jam_mulai' => '13:00',
            'jam_selesai' => '16:00',
        ]);
        LogKegiatan::factory()->create([
            'peserta_id' => $peserta->id,
            'status' => 'menunggu',
            'jam_mulai' => '07:00',
            'jam_selesai' => '10:00',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.totalDisetujui', 2)
                ->where('summary.totalJamDisetujui', 6.5)
            );
    }
}
