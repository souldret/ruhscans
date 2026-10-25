<?php

namespace Database\Factories;

use App\Models\Download;
use App\Models\GameFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Download>
 */
class DownloadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_file_id' => GameFile::factory(),
            'ip' => fake()->ipv4(),
            'day' => now()->toDateString(),
            'created_at' => now(),
        ];
    }
}
