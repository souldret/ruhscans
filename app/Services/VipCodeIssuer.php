<?php

namespace App\Services;

use App\Enums\VipCodeStatus;
use App\Models\CodeBatch;
use App\Models\User;
use App\Models\VipCode;
use App\Support\VipCodeGenerator;
use Illuminate\Support\Facades\DB;

class VipCodeIssuer
{
    public function __construct(private AuditLogger $audit) {}

    public function issue(User $admin, string $name, int $quantity, int $durationDays, ?string $prefix = null): CodeBatch
    {
        $quantity = min(max($quantity, 1), 1000);
        $codes = $this->freshCodes($quantity, $prefix);
        $now = now();

        return DB::transaction(function () use ($admin, $name, $quantity, $durationDays, $codes, $now) {
            $batch = CodeBatch::query()->create([
                'name' => $name,
                'quantity' => $quantity,
                'duration_days' => $durationDays,
                'created_by' => $admin->id,
            ]);

            foreach (array_chunk($codes, 200) as $chunk) {
                VipCode::query()->insert(array_map(fn (string $code) => [
                    'code' => $code,
                    'batch_id' => $batch->id,
                    'duration_days' => $durationDays,
                    'status' => VipCodeStatus::Unused->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            $this->audit->write('code.issued', $batch, null, [
                'quantity' => $quantity,
                'duration_days' => $durationDays,
            ], $admin);

            return $batch;
        });
    }

    /**
     * @return list<string>
     */
    private function freshCodes(int $quantity, ?string $prefix): array
    {
        $codes = [];

        while (count($codes) < $quantity) {
            foreach (VipCodeGenerator::unique($quantity - count($codes), $prefix) as $code) {
                $codes[$code] = $code;
            }

            $taken = VipCode::query()->whereIn('code', array_keys($codes))->pluck('code');

            foreach ($taken as $code) {
                unset($codes[$code]);
            }
        }

        return array_values($codes);
    }
}
