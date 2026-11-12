<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('mahasiswa')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'dpp')
            ->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'mahasiswa'])
                ->default('mahasiswa')
                ->change();
        });
    }
};