<?php

namespace Tests\Feature\Admin;

use App\Models\Peserta;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MahasiswaMonitoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Admin dapat mengakses halaman monitoring mahasiswa.
     */
    public function test_admin_can_access_mahasiswa_monitoring_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data')
            ->has('programStudis')
            ->has('filters')
            ->has('totalCount')
        );
    }

    /**
     * 2. Guest tidak dapat mengakses halaman monitoring dan diredirect ke login.
     */
    public function test_guest_cannot_access_monitoring_page(): void
    {
        $response = $this->get(route('admin.mahasiswa.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 3. Mahasiswa tidak dapat mengakses admin monitoring (HTTP 403).
     */
    public function test_mahasiswa_cannot_access_admin_monitoring(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($mahasiswa)->get(route('admin.mahasiswa.index'));

        $response->assertStatus(403);
    }

    /**
     * 4. Admin dapat melakukan pencarian mahasiswa berdasarkan nama lengkap.
     */
    public function test_admin_can_search_mahasiswa_by_name(): void
    {
        $admin = User::factory()->admin()->create();

        $user1 = User::factory()->mahasiswa()->create(['name' => 'Faris Ahmad']);
        $peserta1 = Peserta::create([
            'user_id' => $user1->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
        ]);

        $user2 = User::factory()->mahasiswa()->create(['name' => 'Budi Santoso']);
        $peserta2 = Peserta::create([
            'user_id' => $user2->id,
            'nama_lengkap' => 'Budi Santoso',
            'nim' => '112024001',
            'program_studi' => 'Teknik Industri',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['search' => 'Faris']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta1->id)
        );
    }

    /**
     * 5. Admin dapat melakukan pencarian mahasiswa berdasarkan NIM.
     */
    public function test_admin_can_search_mahasiswa_by_nim(): void
    {
        $admin = User::factory()->admin()->create();

        $user1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::create([
            'user_id' => $user1->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
        ]);

        $user2 = User::factory()->mahasiswa()->create();
        $peserta2 = Peserta::create([
            'user_id' => $user2->id,
            'nama_lengkap' => 'Budi Santoso',
            'nim' => '112024001',
            'program_studi' => 'Teknik Industri',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['search' => '152024038']));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta1->id)
        );
    }

    /**
     * 6. Admin dapat memfilter mahasiswa berdasarkan program studi.
     */
    public function test_admin_can_filter_mahasiswa_by_program_studi(): void
    {
        $admin = User::factory()->admin()->create();

        $prodiIf = ProgramStudi::create(['nama_prodi' => 'Teknik Informatika', 'fakultas' => 'FTI']);
        $prodiArs = ProgramStudi::create(['nama_prodi' => 'Arsitektur', 'fakultas' => 'FAD']);

        $user1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::create([
            'user_id' => $user1->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
            'program_studi_id' => $prodiIf->id,
        ]);

        $user2 = User::factory()->mahasiswa()->create();
        $peserta2 = Peserta::create([
            'user_id' => $user2->id,
            'nama_lengkap' => 'Citra Dewi',
            'nim' => '212024010',
            'program_studi' => 'Arsitektur',
            'program_studi_id' => $prodiArs->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['program_studi_id' => $prodiIf->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta1->id)
            ->where('mahasiswas.data.0.program_studi', 'Teknik Informatika')
            ->where('mahasiswas.data.0.program_studi_detail.id', $prodiIf->id)
            ->where('mahasiswas.data.0.program_studi_detail.nama_prodi', 'Teknik Informatika')
            ->where('mahasiswas.data.0.program_studi_detail.fakultas', 'FTI')
        );
    }

    /**
     * 7. Validasi filter program_studi_id memastikan id harus ada di tabel program_studis.
     */
    public function test_filter_fails_when_program_studi_id_does_not_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['program_studi_id' => 99999]));

        $response->assertSessionHasErrors('program_studi_id');
    }

    /**
     * 8. Admin dapat melihat halaman detail mahasiswa dengan struktur serialisasi konsisten.
     */
    public function test_admin_can_view_mahasiswa_details(): void
    {
        $admin = User::factory()->admin()->create();

        $prodi = ProgramStudi::create(['nama_prodi' => 'Teknik Informatika', 'fakultas' => 'FTI']);

        $user = User::factory()->mahasiswa()->create([
            'name' => 'Aa Faris',
            'email' => null,
            'nim' => '152024038',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
            'program_studi_id' => $prodi->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.show', $peserta));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Show')
            ->where('mahasiswa.id', $peserta->id)
            ->where('mahasiswa.nama_lengkap', 'Aa Faris Ahmad Shidiq')
            ->where('mahasiswa.program_studi', 'Teknik Informatika')
            ->where('mahasiswa.program_studi_detail.id', $prodi->id)
            ->where('mahasiswa.program_studi_detail.nama_prodi', 'Teknik Informatika')
            ->where('mahasiswa.program_studi_detail.fakultas', 'FTI')
            ->where('mahasiswa.user.name', 'Aa Faris')
        );
    }

    /**
     * 9. Mahasiswa tidak dapat mengakses route detail milik admin (HTTP 403).
     */
    public function test_mahasiswa_cannot_access_another_admin_only_detail_route(): void
    {
        $mahasiswa1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::create([
            'user_id' => $mahasiswa1->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
        ]);

        $mahasiswa2 = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($mahasiswa2)->get(route('admin.mahasiswa.show', $peserta1));

        $response->assertStatus(403);
    }

    /**
     * 10. Data program studi legacy (string) tetap terlihat dan program_studi_detail null ketika program_studi_id bernilai null.
     */
    public function test_legacy_program_studi_data_remains_visible_when_program_studi_id_is_null(): void
    {
        $admin = User::factory()->admin()->create();

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Eko Prasetyo',
            'nim' => '142024005',
            'program_studi' => 'Teknik Kimia',
            'program_studi_id' => null,
        ]);

        // Uji pada halaman index
        $indexResponse = $this->actingAs($admin)->get(route('admin.mahasiswa.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->where('mahasiswas.data.0.program_studi', 'Teknik Kimia')
            ->where('mahasiswas.data.0.program_studi_id', null)
            ->where('mahasiswas.data.0.program_studi_detail', null)
        );

        // Uji pada halaman show
        $showResponse = $this->actingAs($admin)->get(route('admin.mahasiswa.show', $peserta));
        $showResponse->assertStatus(200);
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Show')
            ->where('mahasiswa.program_studi', 'Teknik Kimia')
            ->where('mahasiswa.program_studi_id', null)
            ->where('mahasiswa.program_studi_detail', null)
        );
    }

    /**
     * 11. Login mahasiswa pertama kali otomatis mengisi program_studi_id yang cocok.
     */
    public function test_first_time_mahasiswa_login_assigns_matching_program_studi_id(): void
    {
        $prodi = ProgramStudi::create([
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);

        $nim = '152024038';
        $password = 'password123';

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertNotNull($user->peserta);
        $this->assertSame('Teknik Informatika', $user->peserta->program_studi);
        $this->assertSame($prodi->id, $user->peserta->program_studi_id);
    }

    /**
     * 12. Mahasiswa lama yang memiliki program_studi_id null akan disinkronkan saat login.
     */
    public function test_existing_mahasiswa_with_missing_program_studi_id_gets_synchronized(): void
    {
        $prodi = ProgramStudi::create([
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);

        $nim = '152024038';
        $password = 'password123';

        $user = User::create([
            'name' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'email' => null,
            'password' => Hash::make('password123'),
            'role' => 'mahasiswa',
        ]);

        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => $nim,
            'program_studi' => 'Teknik Informatika',
            'program_studi_id' => null,
        ]);

        $this->assertNull($peserta->program_studi_id);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        $this->assertAuthenticated();

        $peserta->refresh();
        $this->assertSame($prodi->id, $peserta->program_studi_id);
        $this->assertSame('Teknik Informatika', $peserta->program_studi);
    }
}
