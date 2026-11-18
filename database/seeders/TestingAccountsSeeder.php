<?php

namespace Database\Seeders;

use App\Models\DppAccessRequest;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingAccountsSeeder extends Seeder
{
    /**
     * Create local admin and approved DPP fixtures for development and tests.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(ProdiSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@itenas.ac.id'],
            [
                'name' => 'Admin KKN ITENAS',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'nim' => null,
            ]
        );

        $prodi = Prodi::query()->where('kode_prodi', 'IF')->firstOrFail();
        $accessRequest = DppAccessRequest::query()
            ->where('username_sikad', 'dosen.testing')
            ->first();
        $dpp = $accessRequest?->user
            ?? User::query()
                ->where('name', 'Dosen Dummy Testing')
                ->where('role', 'dpp')
                ->first();
        $dpp ??= new User;

        $dpp->fill([
            'name' => 'Dosen Dummy Testing',
            'email' => null,
            'nim' => null,
            'password' => Hash::make('dpptes123'),
            'role' => 'dpp',
        ])->save();

        $dpp->dppProfile()->updateOrCreate(
            ['user_id' => $dpp->id],
            ['kode_prodi' => $prodi->kode_prodi]
        );

        DppAccessRequest::query()->updateOrCreate(
            ['username_sikad' => 'dosen.testing'],
            [
                'nomor_dosen' => 'TEST-DPP-001',
                'nama' => 'Dosen Dummy Testing',
                'kode_fakultas_sikad' => 'FTI',
                'kode_prodi_sikad' => 'IF',
                'approval_status' => DppAccessRequest::STATUS_DISETUJUI,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'user_id' => $dpp->id,
            ]
        );
    }
}
