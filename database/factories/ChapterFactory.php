<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Series;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'series_id' => Series::factory(),
            'number' => fake()->unique()->randomFloat(2, 1, 200),
            'title' => fake()->optional()->sentence(2),
            'published_at' => now(),
            'ruh_tasi_fiyat' => 0,
            'vip_ucretsiz' => false,
            'erken_erisim_bitis' => null,
        ];
    }
}
