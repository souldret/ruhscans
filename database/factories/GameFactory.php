<?php

namespace Database\Factories;

use App\Enums\GameAccess;
use App\Enums\TranslationStatus;
use App\Models\Game;
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
        $title = fake()->unique()->words(3, true);

        return [
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'original_title' => Str::title(fake()->words(2, true)),
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
            'access' => GameAccess::Free,
            'is_published' => true,
            'published_at' => now(),
            'view_count' => 0,
            'download_count' => 0,
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'access' => GameAccess::Vip,
        ]);
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'translation_status' => TranslationStatus::Complete,
            'translation_percent' => 100,
        ]);
    }
}
