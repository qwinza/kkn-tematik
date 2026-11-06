<?php

namespace Database\Factories;

use App\Models\LogKegiatan;
use App\Models\MasterKegiatan;
use App\Models\Peserta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogKegiatan>
 */
class LogKegiatanFactory extends Factory
{
    protected $model = LogKegiatan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peserta_id' => Peserta::factory(),
            'master_kegiatan_id' => MasterKegiatan::factory(),
            'tanggal_kehadiran' => fake()->date(),
            'jam_kehadiran' => fake()->randomElement([
                '07:00 - 10:00 (Pagi)',
                '10:00 - 13:00 (Siang)',
                '13:00 - 16:00 (Sore)',
            ]),
            'lokasi_kegiatan' => fake()->randomElement([
                'Lapangan',
                'Kampus ITENAS',
            ]),
            'deskripsi_kegiatan' => fake()->paragraph(),
            'latitude' => -6.89750000,
            'longitude' => 107.63580000,
            'deskripsi_luaran' => fake()->paragraph(),
            'current_step' => 3,
            'status' => 'menunggu',
        ];
    }

    /**
     * Kondisi data dalam status draft.
     */
    public function draft(int $step = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'current_step' => $step,
            'deskripsi_kegiatan' => $step > 1 ? fake()->paragraph() : null,
            'deskripsi_luaran' => null,
        ]);
    }
}
