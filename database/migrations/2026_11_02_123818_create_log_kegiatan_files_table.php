<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_kegiatan_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('log_kegiatan_id')->constrained('log_kegiatans')->cascadeOnDelete();
            $table->enum('jenis_file', ['foto_kegiatan', 'file_luaran']);
            $table->string('file_path');
            $table->string('nama_file_asli');
            $table->unsignedBigInteger('ukuran_file');
            $table->timestamps();

            $table->index(['log_kegiatan_id', 'jenis_file']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_kegiatan_files');
    }
};
