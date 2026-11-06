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
        $programStudis = DB::table('program_studis')->get(['id', 'nama_prodi', 'fakultas']);

        foreach ($programStudis as $programStudi) {
            if (! DB::table('prodi')->where('nama_prodi', $programStudi->nama_prodi)->exists()) {
                throw new RuntimeException("Program studi [{$programStudi->nama_prodi}] has no matching prodi record.");
            }
        }

        Schema::table('prodi', function (Blueprint $table) {
            $table->string('fakultas')->nullable();
        });

        foreach ($programStudis as $programStudi) {
            DB::table('prodi')
                ->where('nama_prodi', $programStudi->nama_prodi)
                ->update(['fakultas' => $programStudi->fakultas]);
        }

        $programStudiNames = $programStudis->keyBy('id');

        foreach (DB::table('pesertas')->get(['id', 'program_studi_id', 'program_studi', 'kode_prodi']) as $peserta) {
            $programStudiName = $programStudiNames->get($peserta->program_studi_id)?->nama_prodi
                ?? $peserta->program_studi;

            if ($peserta->kode_prodi !== null || $programStudiName === null) {
                continue;
            }

            $kodeProdi = DB::table('prodi')
                ->where('nama_prodi', trim($programStudiName))
                ->value('kode_prodi');

            if ($kodeProdi !== null) {
                DB::table('pesertas')->where('id', $peserta->id)->update(['kode_prodi' => $kodeProdi]);
            }
        }

        DB::table('prodi')->whereNull('fakultas')->update(['fakultas' => '']);

        Schema::table('prodi', function (Blueprint $table) {
            $table->string('fakultas')->nullable(false)->change();
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->dropForeign(['program_studi_id']);
            $table->dropColumn('program_studi_id');
        });

        Schema::dropIfExists('program_studis');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('program_studis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_prodi')->unique();
            $table->string('fakultas');
            $table->timestamps();
        });

        Schema::table('pesertas', function (Blueprint $table) {
            $table->foreignId('program_studi_id')
                ->nullable()
                ->constrained('program_studis')
                ->restrictOnDelete();
        });

        Schema::table('prodi', function (Blueprint $table) {
            $table->dropColumn('fakultas');
        });
    }
};
