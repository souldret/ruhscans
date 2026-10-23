<?php

namespace Database\Factories;

use App\Enums\VipCodeStatus;
use App\Models\CodeBatch;
use App\Models\VipCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VipCode>
 */
class VipCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4)),
            'batch_id' => CodeBatch::factory(),
            'duration_days' => 30,
            'status' => VipCodeStatus::Unused,
            'used_by' => null,
            'used_at' => null,
        ];
    }
}
