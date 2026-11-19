<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\Series;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reportable_type' => Series::class,
            'reportable_id' => Series::factory(),
            'reason' => fake()->sentence(),
            'status' => ReportStatus::Bekliyor,
        ];
    }
}
