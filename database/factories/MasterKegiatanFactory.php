<?php

namespace Database\Factories;

use App\Models\MasterKegiatan;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterKegiatan>
 */
class MasterKegiatanFactory extends Factory
{
    protected $model = MasterKegiatan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_kegiatan' => 'KKN-'.fake()->unique()->numerify('###'),
            'nama_kegiatan' => fake()->sentence(3),
            'kategori' => fake()->randomElement(['Sosialisasi', 'Pelatihan', 'Lingkungan', 'Kesehatan', 'Teknologi']),
            'kode_prodi' => Prodi::factory(),
        ];
    }
}
