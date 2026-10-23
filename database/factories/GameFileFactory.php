<?php

namespace Database\Factories;

use App\Enums\Platform;
use App\Models\Game;
use App\Models\GameFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameFile>
 */
class GameFileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'label' => 'Windows sürümü',
            'platform' => Platform::Windows,
            'storage_path' => 'games/'.fake()->uuid().'.zip',
            'size_bytes' => fake()->numberBetween(50_000_000, 2_000_000_000),
            'version' => '1.0',
        ];
    }
}
