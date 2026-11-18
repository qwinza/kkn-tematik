<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpp_access_requests', function (Blueprint $table) {
            $table->id();

            // Identitas dosen dari SIKAD
            $table->string('username_sikad', 100)->unique();
            $table->string('nomor_dosen', 30)->unique();
            $table->string('nama');

            // Informasi akademik dari respons API SIKAD
            $table->string('kode_fakultas_sikad', 30)->nullable();
            $table->string('kode_prodi_sikad', 30)->nullable();
            $table->string('nidn', 50)->nullable();
            $table->string('gelar_depan', 100)->nullable();
            $table->string('gelar_belakang', 150)->nullable();
            $table->string('status_sikad', 20)->nullable();

            // Status persetujuan Admin
            $table->string('approval_status', 20)
                ->default('menunggu')
                ->index();

            // Informasi pemeriksaan oleh Admin
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Terhubung ke akun setelah disetujui
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpp_access_requests');
    }
};