<?php

namespace Database\Factories;

use App\Models\CodeAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodeAttempt>
 */
class CodeAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip' => fake()->ipv4(),
        ];
    }
}
