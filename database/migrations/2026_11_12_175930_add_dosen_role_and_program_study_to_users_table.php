<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('mahasiswa')->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('kode_prodi', 20)->nullable()->after('role');
            $table->foreign('kode_prodi')
                ->references('kode_prodi')
                ->on('prodi')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'dosen')->update(['role' => 'mahasiswa']);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['kode_prodi']);
            $table->dropColumn('kode_prodi');
            $table->enum('role', ['admin', 'mahasiswa'])->default('mahasiswa')->change();
        });
    }
};
