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
        Schema::table('log_kegiatans', function (Blueprint $table) {
            $table->foreignId('rencana_kehadiran_id')
                ->nullable()
                ->after('peserta_id')
                ->constrained('rencana_kehadirans')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_kegiatans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rencana_kehadiran_id');
        });
    }
};
