<?php

namespace Database\Factories;

use App\Models\TranslatorGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TranslatorGroup>
 */
class TranslatorGroupFactory extends Factory
{
    protected $model = TranslatorGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
        ];
    }
}
