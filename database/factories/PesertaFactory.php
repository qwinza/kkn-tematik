<?php

namespace Database\Factories;

use App\Models\Peserta;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peserta>
 */
class PesertaFactory extends Factory
{
    protected $model = Peserta::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->mahasiswa(),
            'nama_lengkap' => fake()->name(),
            'nim' => fake()->unique()->numerify('152024###'),
            'kode_prodi' => Prodi::factory(),
        ];
    }
}
