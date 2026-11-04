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
        Schema::create('rencana_kehadirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_id')->constrained('pesertas')->cascadeOnDelete();
            $table->date('tanggal_rencana');
            $table->enum('jam_kehadiran', [
                '07:00 - 10:00 (Pagi)',
                '10:00 - 13:00 (Siang)',
                '13:00 - 16:00 (Sore)',
            ]);
            $table->timestamps();

            $table->index(['peserta_id', 'tanggal_rencana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_kehadirans');
    }
};
