<?php

namespace Database\Factories;

use App\Models\CodeBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CodeBatch>
 */
class CodeBatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Parti '.fake()->unique()->numerify('###'),
            'quantity' => 10,
            'duration_days' => 30,
            'created_by' => User::factory()->admin(),
        ];
    }
}
