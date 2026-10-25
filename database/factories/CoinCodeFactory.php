<?php

namespace Database\Factories;

use App\Enums\CoinCodeStatus;
use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CoinCode>
 */
class CoinCodeFactory extends Factory
{
    protected $model = CoinCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4)),
            'batch_id' => CoinCodeBatch::factory(),
            'coin_amount' => 100,
            'status' => CoinCodeStatus::Unused,
            'used_by' => null,
            'used_at' => null,
        ];
    }
}
