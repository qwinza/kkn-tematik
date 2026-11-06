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
        if (! Schema::hasColumn('pesertas', 'program_studi')) {
            Schema::table('pesertas', function (Blueprint $table) {
                $table->string('program_studi')->nullable();
            });
        }

        Schema::table('pesertas', function (Blueprint $table) {
            $table->foreignId('program_studi_id')
                ->nullable()
                ->constrained('program_studis')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropForeign(['program_studi_id']);
            $table->dropColumn('program_studi_id');
        });
    }
};
