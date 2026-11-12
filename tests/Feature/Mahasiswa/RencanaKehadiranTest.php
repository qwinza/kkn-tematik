<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\LogKegiatan;
use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\RencanaKehadiran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RencanaKehadiranTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper membuat user mahasiswa beserta profil Peserta.
     */
    protected function createMahasiswaPeserta(array $pesertaOverrides = [], array $userOverrides = []): array
    {
        $user = User::factory()->mahasiswa()->create($userOverrides);

        $peserta = Peserta::create(array_merge([
            'user_id' => $user->id,
            'nama_lengkap' => $user->name,
            'nim' => $user->nim ?? '152024999',
            'program_studi' => 'Teknik Informatika',
        ], $pesertaOverrides));

        return [$user, $peserta];
    }

    /**
     * 1. Mahasiswa can access their own attendance planning page.
     */
    public function test_mahasiswa_can_access_their_own_attendance_planning_page(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();

        $response = $this->actingAs($user)->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Mahasiswa/RencanaKehadiran/Index')
            ->has('peserta')
            ->has('rencanaKehadirans')
            ->has('sessions')
            ->where('today', now()->toDateString())
        );
    }

    /**
     * 2. Guest cannot access the page.
     */
    public function test_guest_cannot_access_the_page(): void
    {
        $response = $this->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 3. Admin cannot access student attendance planning.
     */
    public function test_admin_cannot_access_student_attendance_planning(): void
    {
        $admin = User::factory()->admin()->create();

        $responseIndex = $this->actingAs($admin)->get(route('mahasiswa.rencana-kehadiran.index'));
        $responseIndex->assertStatus(403);

        $responseStore = $this->actingAs($admin)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => Carbon::today()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $responseStore->assertStatus(403);
    }

    /**
     * 4. Mahasiswa can create an attendance plan.
     */
    public function test_mahasiswa_can_create_an_attendance_plan(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();

        $tanggalRencana = Carbon::today()->addDays(2)->toDateString();

        $response = $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => $tanggalRencana,
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $response->assertRedirect(route('mahasiswa.rencana-kehadiran.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rencana_kehadirans', [
            'peserta_id' => $peserta->id,
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $plan = RencanaKehadiran::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($plan);
        $this->assertSame($tanggalRencana, $plan->tanggal_rencana->format('Y-m-d'));
    }

    /**
     * 5. Submitted plan belongs to the authenticated student's Peserta.
     */
    public function test_submitted_plan_belongs_to_authenticated_students_peserta(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();

        $tanggalRencana = Carbon::today()->toDateString();

        $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => $tanggalRencana,
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $plan = RencanaKehadiran::first();

        $this->assertNotNull($plan);
        $this->assertSame($peserta->id, $plan->peserta_id);
    }

    /**
     * 6. A student cannot assign a plan to another participant by submitting peserta_id.
     */
    public function test_student_cannot_assign_plan_to_another_participant_by_submitting_peserta_id(): void
    {
        [$userA, $pesertaA] = $this->createMahasiswaPeserta();
        [$userB, $pesertaB] = $this->createMahasiswaPeserta([
            'nim' => '152024888',
        ]);

        $tanggalRencana = Carbon::today()->addDay()->toDateString();

        // Mahasiswa A mencoba menginjeksi peserta_id milik Mahasiswa B
        $response = $this->actingAs($userA)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'peserta_id' => $pesertaB->id,
            'tanggal_rencana' => $tanggalRencana,
            'jam_kehadiran' => '13:00 - 16:00 (Sore)',
        ]);

        $response->assertRedirect(route('mahasiswa.rencana-kehadiran.index'));

        // Rencana yang tersimpan harus tetap milik peserta A
        $this->assertDatabaseHas('rencana_kehadirans', [
            'peserta_id' => $pesertaA->id,
            'jam_kehadiran' => '13:00 - 16:00 (Sore)',
        ]);

        $plan = RencanaKehadiran::where('peserta_id', $pesertaA->id)->first();
        $this->assertNotNull($plan);
        $this->assertSame($tanggalRencana, $plan->tanggal_rencana->format('Y-m-d'));

        // Peserta B tidak boleh memiliki rencana kehadiran
        $this->assertDatabaseMissing('rencana_kehadirans', [
            'peserta_id' => $pesertaB->id,
        ]);
    }

    /**
     * 7. Past dates are accepted (mendukung pengisian tanggal sebelumnya).
     */
    public function test_past_dates_are_accepted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', config('app.timezone')));

        [$user, $peserta] = $this->createMahasiswaPeserta();

        $yesterday = '2026-11-01';

        $response = $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => $yesterday,
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $response->assertRedirect(route('mahasiswa.rencana-kehadiran.index'));
        $response->assertSessionHas('success');

        $plan = RencanaKehadiran::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($plan);
        $this->assertSame($yesterday, $plan->tanggal_rencana->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /**
     * Today's date is accepted.
     */
    public function test_today_date_is_accepted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', config('app.timezone')));

        [$user, $peserta] = $this->createMahasiswaPeserta();

        $response = $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => '2026-11-02',
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $response->assertRedirect(route('mahasiswa.rencana-kehadiran.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rencana_kehadirans', [
            'peserta_id' => $peserta->id,
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $plan = RencanaKehadiran::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($plan);
        $this->assertSame('2026-11-02', $plan->tanggal_rencana->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /**
     * Future dates are accepted.
     */
    public function test_future_dates_are_accepted(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', config('app.timezone')));

        [$user, $peserta] = $this->createMahasiswaPeserta();

        $response = $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => '2026-11-05',
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $response->assertRedirect(route('mahasiswa.rencana-kehadiran.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rencana_kehadirans', [
            'peserta_id' => $peserta->id,
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        $plan = RencanaKehadiran::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($plan);
        $this->assertSame('2026-11-05', $plan->tanggal_rencana->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /**
     * Inertia today prop uses application-local date.
     */
    public function test_inertia_today_prop_uses_application_local_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 23:45:00', config('app.timezone')));

        [$user, $peserta] = $this->createMahasiswaPeserta();

        $response = $this->actingAs($user)->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Mahasiswa/RencanaKehadiran/Index')
            ->where('today', '2026-11-02')
        );

        Carbon::setTestNow();
    }

    /**
     * 8. Invalid session values are rejected.
     */
    public function test_invalid_session_values_are_rejected(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();

        $response = $this->actingAs($user)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => Carbon::today()->toDateString(),
            'jam_kehadiran' => 'Malam (19.00 - 21.00)',
        ]);

        $response->assertSessionHasErrors('jam_kehadiran');
        $this->assertDatabaseCount('rencana_kehadirans', 0);
    }

    /**
     * 9. Student only sees their own attendance plans.
     */
    public function test_student_only_sees_their_own_attendance_plans(): void
    {
        [$userA, $pesertaA] = $this->createMahasiswaPeserta();
        [$userB, $pesertaB] = $this->createMahasiswaPeserta(['nim' => '112024001']);

        // Buat 2 rencana untuk Peserta A
        RencanaKehadiran::create([
            'peserta_id' => $pesertaA->id,
            'tanggal_rencana' => Carbon::today()->addDay()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        RencanaKehadiran::create([
            'peserta_id' => $pesertaA->id,
            'tanggal_rencana' => Carbon::today()->addDays(2)->toDateString(),
            'jam_kehadiran' => '10:00 - 13:00 (Siang)',
        ]);

        // Buat 1 rencana untuk Peserta B
        RencanaKehadiran::create([
            'peserta_id' => $pesertaB->id,
            'tanggal_rencana' => Carbon::today()->addDays(3)->toDateString(),
            'jam_kehadiran' => '13:00 - 16:00 (Sore)',
        ]);

        $response = $this->actingAs($userA)->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Mahasiswa/RencanaKehadiran/Index')
            ->has('rencanaKehadirans.data', 2)
            ->where('rencanaKehadirans.data.0.peserta_id', $pesertaA->id)
            ->where('rencanaKehadirans.data.1.peserta_id', $pesertaA->id)
        );
    }

    public function test_attendance_history_includes_related_activity_logs(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();
        $rencana = RencanaKehadiran::create([
            'peserta_id' => $peserta->id,
            'tanggal_rencana' => Carbon::today()->addDay()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $log = LogKegiatan::factory()->draft()->create([
            'peserta_id' => $peserta->id,
            'rencana_kehadiran_id' => $rencana->id,
        ]);

        $this->actingAs($user)
            ->get(route('mahasiswa.schedule.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mahasiswa/RencanaKehadiran/Index')
                ->where('rencanaKehadirans.data.0.log_kegiatans.0.id', $log->id)
                ->where('rencanaKehadirans.data.0.log_kegiatans.0.status', 'draft')
            );
    }

    /**
     * 10. Student identity is loaded from their own Peserta record.
     */
    public function test_student_identity_is_loaded_from_their_own_peserta_record(): void
    {
        $prodi = Prodi::factory()->create([
            'nama_prodi' => 'Teknik Geodesi',
            'fakultas' => 'FTSP',
        ]);

        [$user, $peserta] = $this->createMahasiswaPeserta([
            'nama_lengkap' => 'Muhammad Rifqi',
            'nim' => '122024045',
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Mahasiswa/RencanaKehadiran/Index')
            ->where('peserta.nama_lengkap', 'Muhammad Rifqi')
            ->where('peserta.nim', '122024045')
            ->where('peserta.program_studi', 'Teknik Geodesi')
            ->where('peserta.program_studi_detail.nama_prodi', 'Teknik Geodesi')
            ->where('peserta.program_studi_detail.fakultas', 'FTSP')
        );
    }

    /**
     * 11. Legacy program_studi remains supported.
     */
    public function test_legacy_program_studi_remains_supported(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta([
            'nama_lengkap' => 'Alumni Mahasiswa',
            'nim' => '152020001',
            'program_studi' => 'Teknik Kimia Lama',
        ]);

        $response = $this->actingAs($user)->get(route('mahasiswa.rencana-kehadiran.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Mahasiswa/RencanaKehadiran/Index')
            ->where('peserta.program_studi', 'Teknik Kimia Lama')
            ->where('peserta.program_studi_detail', null)
        );
    }

    /**
     * 12. Student without Peserta record receives HTTP 404.
     */
    public function test_student_without_peserta_record_receives_http_404(): void
    {
        $userWithoutPeserta = User::factory()->mahasiswa()->create();

        // Akses index tanpa profil Peserta
        $responseIndex = $this->actingAs($userWithoutPeserta)->get(route('mahasiswa.rencana-kehadiran.index'));
        $responseIndex->assertStatus(404);

        // Submit form tanpa profil Peserta
        $responseStore = $this->actingAs($userWithoutPeserta)->post(route('mahasiswa.rencana-kehadiran.store'), [
            'tanggal_rencana' => Carbon::today()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);
        $responseStore->assertStatus(404);
    }

    /**
     * 13. Mahasiswa can reschedule their own attendance plan.
     */
    public function test_mahasiswa_can_reschedule_their_attendance_plan(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();
        $rencana = RencanaKehadiran::create([
            'peserta_id' => $peserta->id,
            'tanggal_rencana' => Carbon::today()->addDay()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $newDate = Carbon::today()->addDays(3)->toDateString();

        $response = $this->actingAs($user)->put(route('mahasiswa.schedule.update', $rencana), [
            'tanggal_rencana' => $newDate,
            'jam_kehadiran' => '13:00 - 16:00 (Sore)',
        ]);

        $response->assertRedirect(route('mahasiswa.schedule.index'));
        $response->assertSessionHas('success', 'Jadwal kehadiran berhasil diperbarui.');

        $rencana->refresh();
        $this->assertSame($newDate, $rencana->tanggal_rencana->format('Y-m-d'));
        $this->assertSame('13:00 - 16:00 (Sore)', $rencana->jam_kehadiran);
    }

    /**
     * 14. Mahasiswa cannot reschedule another student's plan.
     */
    public function test_mahasiswa_cannot_reschedule_another_students_plan(): void
    {
        [$userA, $pesertaA] = $this->createMahasiswaPeserta();
        [, $pesertaB] = $this->createMahasiswaPeserta(['nim' => '152024777']);

        $rencanaB = RencanaKehadiran::create([
            'peserta_id' => $pesertaB->id,
            'tanggal_rencana' => Carbon::today()->addDay()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $this->actingAs($userA)
            ->put(route('mahasiswa.schedule.update', $rencanaB), [
                'tanggal_rencana' => Carbon::today()->addDays(4)->toDateString(),
                'jam_kehadiran' => '13:00 - 16:00 (Sore)',
            ])
            ->assertForbidden();

        $rencanaB->refresh();
        $this->assertSame('07:00 - 10:00 (Pagi)', $rencanaB->jam_kehadiran);
    }

    /**
     * 15. Reschedule validates the session value.
     */
    public function test_reschedule_validates_session_value(): void
    {
        [$user, $peserta] = $this->createMahasiswaPeserta();
        $rencana = RencanaKehadiran::create([
            'peserta_id' => $peserta->id,
            'tanggal_rencana' => Carbon::today()->addDay()->toDateString(),
            'jam_kehadiran' => '07:00 - 10:00 (Pagi)',
        ]);

        $this->actingAs($user)
            ->put(route('mahasiswa.schedule.update', $rencana), [
                'tanggal_rencana' => Carbon::today()->addDays(3)->toDateString(),
                'jam_kehadiran' => 'Malam (19.00 - 21.00)',
            ])
            ->assertSessionHasErrors('jam_kehadiran');

        $rencana->refresh();
        $this->assertSame('07:00 - 10:00 (Pagi)', $rencana->jam_kehadiran);
    }
}
