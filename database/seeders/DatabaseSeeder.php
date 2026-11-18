<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed reference data, participants, and local testing accounts.
     */
    public function run(): void
    {
        $this->call(TestingAccountsSeeder::class);
    }
}
