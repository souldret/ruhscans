<?php

namespace Database\Factories;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Models\Series;
use App\Models\TranslatorGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Series>
 */
class SeriesFactory extends Factory
{
    protected $model = Series::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'translator_group_id' => TranslatorGroup::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'alternative_names' => [fake()->sentence(2)],
            'description' => fake()->paragraph(),
            'cover_path' => null,
            'type' => fake()->randomElement(SeriesType::cases()),
            'status' => fake()->randomElement(SeriesStatus::cases()),
            'content_level' => fake()->randomElement(ContentLevel::cases()),
            'year' => fake()->numberBetween(2015, 2026),
            'author' => fake()->name(),
            'artist' => fake()->name(),
            'hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['hidden' => true]);
    }
}
