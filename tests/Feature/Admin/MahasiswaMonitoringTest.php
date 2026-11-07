<?php

namespace Tests\Feature\Admin;

use App\Models\Peserta;
use App\Models\Prodi;
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
            ->has('prodis')
            ->has('filters')
            ->has('totalCount')
        );
    }

    public function test_admin_can_open_student_details_using_an_encrypted_route_key(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create(['user_id' => $student->id]);
        $url = route('admin.mahasiswa.show', $peserta);

        $this->assertSame(0, preg_match('#/admin/mahasiswa/\\d+$#', (string) parse_url($url, PHP_URL_PATH)));

        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Mahasiswa/Show')
                ->where('mahasiswa.id', $peserta->id)
                ->has('mahasiswa.route_key')
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

    public function test_admin_can_search_mahasiswa_by_program_studi_name(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create([
            'nama_prodi' => 'Teknik Informatika',
            'fakultas' => 'FTI',
        ]);
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create([
            'user_id' => $user->id,
            'kode_prodi' => $prodi->kode_prodi,
            'program_studi' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['search' => 'Informatika']));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta->id)
        );
    }

    public function test_admin_can_sort_mahasiswa_by_name_ascending(): void
    {
        $admin = User::factory()->admin()->create();
        $zUser = User::factory()->mahasiswa()->create();
        $zPeserta = Peserta::factory()->create([
            'user_id' => $zUser->id,
            'nama_lengkap' => 'Zahra',
        ]);
        $aUser = User::factory()->mahasiswa()->create();
        $aPeserta = Peserta::factory()->create([
            'user_id' => $aUser->id,
            'nama_lengkap' => 'Alya',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', [
            'sort' => 'nama_lengkap',
            'direction' => 'asc',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->where('mahasiswas.data.0.id', $aPeserta->id)
            ->where('mahasiswas.data.1.id', $zPeserta->id)
            ->where('filters.sort', 'nama_lengkap')
            ->where('filters.direction', 'asc')
        );
    }

    /**
     * 6. Admin dapat memfilter mahasiswa berdasarkan program studi.
     */
    public function test_admin_can_filter_mahasiswa_by_program_studi(): void
    {
        $admin = User::factory()->admin()->create();

        $prodiIf = Prodi::create(['kode_prodi' => 'TI-01', 'nama_prodi' => 'Teknik Informatika', 'fakultas' => 'FTI']);
        $prodiArs = Prodi::create(['kode_prodi' => 'AR-01', 'nama_prodi' => 'Arsitektur', 'fakultas' => 'FAD']);

        $user1 = User::factory()->mahasiswa()->create();
        $peserta1 = Peserta::create([
            'user_id' => $user1->id,
            'nama_lengkap' => 'Aa Faris Ahmad Shidiq',
            'nim' => '152024038',
            'program_studi' => 'Teknik Informatika',
            'kode_prodi' => $prodiIf->kode_prodi,
        ]);

        $user2 = User::factory()->mahasiswa()->create();
        $peserta2 = Peserta::create([
            'user_id' => $user2->id,
            'nama_lengkap' => 'Citra Dewi',
            'nim' => '212024010',
            'program_studi' => 'Arsitektur',
            'kode_prodi' => $prodiArs->kode_prodi,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', [
            'kode_prodi' => $prodiIf->kode_prodi,
            'sort' => 'id',
            'direction' => 'desc',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta1->id)
            ->where('mahasiswas.data.0.program_studi', 'Teknik Informatika')
            ->where('mahasiswas.data.0.kode_prodi', $prodiIf->kode_prodi)
            ->where('mahasiswas.data.0.prodi.nama_prodi', 'Teknik Informatika')
            ->where('mahasiswas.data.0.prodi.fakultas', 'FTI')
            ->where('filters.kode_prodi', $prodiIf->kode_prodi)
            ->where('filters.sort', 'id')
            ->where('filters.direction', 'desc')
        );
    }

    public function test_program_study_filter_includes_legacy_names_without_a_prodi_code(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create(['nama_prodi' => 'Teknik Informatika']);
        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::factory()->create([
            'user_id' => $user->id,
            'kode_prodi' => null,
            'program_studi' => 'Teknik Informatika',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', [
            'kode_prodi' => $prodi->kode_prodi,
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->has('mahasiswas.data', 1)
            ->where('mahasiswas.data.0.id', $peserta->id)
        );
    }

    /**
     * 7. Validasi filter kode_prodi memastikan kode harus ada di tabel prodi.
     */
    public function test_filter_fails_when_kode_prodi_does_not_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.index', ['kode_prodi' => 'MISSING']));

        $response->assertSessionHasErrors('kode_prodi');
    }

    /**
     * 8. Admin dapat melihat halaman detail mahasiswa dengan struktur serialisasi konsisten.
     */
    public function test_admin_can_view_mahasiswa_details(): void
    {
        $admin = User::factory()->admin()->create();

        $prodi = Prodi::create(['kode_prodi' => 'TI-02', 'nama_prodi' => 'Teknik Informatika', 'fakultas' => 'FTI']);

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
            'kode_prodi' => $prodi->kode_prodi,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.mahasiswa.show', $peserta));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Show')
            ->where('mahasiswa.id', $peserta->id)
            ->where('mahasiswa.nama_lengkap', 'Aa Faris Ahmad Shidiq')
            ->where('mahasiswa.program_studi', 'Teknik Informatika')
            ->where('mahasiswa.kode_prodi', $prodi->kode_prodi)
            ->where('mahasiswa.prodi.nama_prodi', 'Teknik Informatika')
            ->where('mahasiswa.prodi.fakultas', 'FTI')
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
     * 10. Data program studi lama tetap terlihat ketika peserta belum memiliki relasi prodi.
     */
    public function test_legacy_program_studi_data_remains_visible_when_kode_prodi_is_null(): void
    {
        $admin = User::factory()->admin()->create();

        $user = User::factory()->mahasiswa()->create();
        $peserta = Peserta::create([
            'user_id' => $user->id,
            'nama_lengkap' => 'Eko Prasetyo',
            'nim' => '142024005',
            'program_studi' => 'Teknik Kimia',
        ]);

        // Uji pada halaman index
        $indexResponse = $this->actingAs($admin)->get(route('admin.mahasiswa.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Index')
            ->where('mahasiswas.data.0.program_studi', 'Teknik Kimia')
            ->where('mahasiswas.data.0.kode_prodi', null)
            ->where('mahasiswas.data.0.prodi', null)
        );

        // Uji pada halaman show
        $showResponse = $this->actingAs($admin)->get(route('admin.mahasiswa.show', $peserta));
        $showResponse->assertStatus(200);
        $showResponse->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mahasiswa/Show')
            ->where('mahasiswa.program_studi', 'Teknik Kimia')
            ->where('mahasiswa.kode_prodi', null)
            ->where('mahasiswa.prodi', null)
        );
    }

    public function test_non_mahasiswa_account_cannot_login_with_a_numeric_identifier(): void
    {
        $nim = '152024038';
        User::factory()->admin()->create([
            'nim' => $nim,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    /**
     * 12. Mahasiswa lama yang belum memiliki kode_prodi akan disinkronkan saat login.
     */
    public function test_existing_mahasiswa_with_missing_kode_prodi_gets_synchronized(): void
    {
        $prodi = Prodi::create([
            'kode_prodi' => 'TI-04',
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
        ]);

        $this->assertNull($peserta->kode_prodi);

        $response = $this->post(route('login'), [
            'identifier' => $nim,
            'password' => $password,
        ]);

        $this->assertAuthenticated();

        $peserta->refresh();
        $this->assertSame($prodi->kode_prodi, $peserta->kode_prodi);
        $this->assertSame('Teknik Informatika', $peserta->program_studi);
    }

    public function test_admin_can_set_a_student_password_without_exposing_the_hash(): void
    {
        $admin = User::factory()->admin()->create();
        $prodi = Prodi::factory()->create();
        $student = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Contoh',
            'nim' => '202600000003',
            'email' => 'mahasiswa@example.com',
            'must_change_password' => false,
        ]);
        $peserta = Peserta::factory()->create([
            'user_id' => $student->id,
            'nama_lengkap' => 'Mahasiswa Contoh',
            'nim' => '202600000003',
            'kode_prodi' => $prodi->kode_prodi,
            'program_studi' => $prodi->nama_prodi,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.mahasiswa.update', $peserta), [
                'nama_lengkap' => 'Mahasiswa Contoh',
                'nim' => '202600000003',
                'email' => 'mahasiswa@example.com',
                'kode_prodi' => $prodi->kode_prodi,
                'password' => 'PasswordAdmin123!',
                'password_confirmation' => 'PasswordAdmin123!',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('PasswordAdmin123!', $student->fresh()->password));
        $this->assertTrue($student->fresh()->must_change_password);

        $this->actingAs($admin)
            ->get(route('admin.mahasiswa.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('mahasiswas.data.0.user.password')
            );
    }
}
