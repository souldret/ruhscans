<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'order' => fake()->unique()->numberBetween(1, 500),
            'r2_path' => 'chapters/'.fake()->uuid().'.webp',
            'width' => 800,
            'height' => 1200,
        ];
    }
}
