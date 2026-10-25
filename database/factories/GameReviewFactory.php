<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameReview>
 */
class GameReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'positive' => true,
            'body' => fake()->sentence(12),
        ];
    }
}
