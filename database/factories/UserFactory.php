<?php

namespace Database\Factories;

use App\Enums\CoinTransactionType;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => UserRole::Member,
            'avatar_path' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function rich(): static
    {
        return $this->afterCreating(function (User $user): void {
            app(CoinService::class)->credit($user, 1000, CoinTransactionType::Bonus, note: 'test bakiyesi');
        });
    }
}
