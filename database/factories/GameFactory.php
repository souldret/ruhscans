<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\Game;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->title(3);

        return [
            'title' => Str::title($title),
            'slug' => Slug::from($title).'-'.fake()->unique()->numerify('###'),
            'original_title' => Str::title($this->title(2)),
            'developer' => fake()->company(),
            'description' => fake()->paragraph(),
            'cover_path' => 'placeholders/cover.svg',
            'banner_path' => 'placeholders/banner.svg',
            'screenshots' => ['placeholders/shot.svg'],
            'game_version' => '1.0',
            'translation_version' => '1.0',
            'translation_status' => TranslationStatus::Ongoing,
            'translation_percent' => fake()->numberBetween(10, 90),
            'size_mb' => fake()->numberBetween(200, 4000),
            'coin_price' => 0,
            'is_published' => true,
            'published_at' => now(),
            'view_count' => 0,
            'download_count' => 0,
        ];
    }

    private function title(int $words): string
    {
        $value = fake()->unique()->words($words, true);

        return is_string($value) ? $value : implode(' ', $value);
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'translation_status' => TranslationStatus::Complete,
            'translation_percent' => 100,
        ]);
    }
}
