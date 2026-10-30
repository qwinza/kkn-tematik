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
        Schema::create('log_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->foreignId('master_kegiatan_id')->constrained('master_kegiatans')->cascadeOnDelete();
            $table->date('tanggal_kehadiran');
            $table->enum('jam_kehadiran', [
                '07:00 - 10:00 (Pagi)',
                '10:00 - 13:00 (Siang)',
                '13:00 - 16:00 (Sore)',
            ]);
            $table->enum('lokasi_kegiatan', [
                'Lapangan',
                'Kampus ITENAS',
            ]);
            $table->text('deskripsi_kegiatan');
            $table->string('foto_kegiatan_path');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('deskripsi_luaran');
            $table->string('file_luaran_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_kegiatans');
    }
};
