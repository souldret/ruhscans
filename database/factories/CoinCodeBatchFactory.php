<?php

namespace Database\Factories;

use App\Models\CoinCodeBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoinCodeBatch>
 */
class CoinCodeBatchFactory extends Factory
{
    protected $model = CoinCodeBatch::class;

    public function definition(): array
    {
        return [
            'name' => 'Parti '.fake()->unique()->numerify('###'),
            'quantity' => 10,
            'coin_amount' => 100,
            'created_by' => User::factory()->admin(),
        ];
    }
}
