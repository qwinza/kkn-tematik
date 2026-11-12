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
            $table->string('keterangan_tempat')->nullable()->after('lokasi_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_kegiatans', function (Blueprint $table) {
            $table->dropColumn('keterangan_tempat');
        });
    }
};
