<?php

namespace Database\Factories;

use App\Models\CoinPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoinPackage>
 */
class CoinPackageFactory extends Factory
{
    protected $model = CoinPackage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->numerify('Paket ###'),
            'coin_amount' => 500,
            'bonus_coin' => 50,
            'price_label' => '99 TL',
            'url' => 'https://odeme.example/paket',
            'button_text' => 'Satın Al',
            'badge' => null,
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => false,
        ];
    }
}
