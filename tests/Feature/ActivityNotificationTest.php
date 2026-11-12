<?php

namespace Tests\Feature;

use App\Models\LogKegiatan;
use App\Models\Peserta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_notifications_cover_submitted_and_reviewed_logs(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $mahasiswa->id]);

        LogKegiatan::factory()->create(['peserta_id' => $peserta->id, 'status' => 'menunggu']);
        LogKegiatan::factory()->create(['peserta_id' => $peserta->id, 'status' => 'disetujui']);
        LogKegiatan::factory()->create(['peserta_id' => $peserta->id, 'status' => 'ditolak']);
        LogKegiatan::factory()->draft()->create(['peserta_id' => $peserta->id]);

        $this->actingAs($mahasiswa)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('activityNotifications', 3)
                ->has('activityNotifications.0.status')
                ->has('activityNotifications.0.route_key')
                ->has('activityNotifications.0.kegiatan')
            );
    }

    public function test_mahasiswa_only_sees_notifications_for_their_own_logs(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $mahasiswa->id]);
        $otherPeserta = Peserta::factory()->create();

        LogKegiatan::factory()->create(['peserta_id' => $peserta->id, 'status' => 'disetujui']);
        LogKegiatan::factory()->count(2)->create(['peserta_id' => $otherPeserta->id, 'status' => 'disetujui']);

        $this->actingAs($mahasiswa)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('activityNotifications', 1)
            );
    }

    public function test_admin_does_not_receive_activity_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        LogKegiatan::factory()->create(['status' => 'disetujui']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('activityNotifications', 0)
            );
    }
}
