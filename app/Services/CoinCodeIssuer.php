<?php

namespace App\Services;

use App\Enums\CoinCodeStatus;
use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use App\Models\User;
use App\Support\VipCodeGenerator;
use Illuminate\Support\Facades\DB;

class CoinCodeIssuer
{
    public function __construct(private AuditLogger $audit) {}

    public function issue(User $admin, string $name, int $quantity, int $coinAmount, ?string $prefix = null): CoinCodeBatch
    {
        $quantity = min(max($quantity, 1), 1000);
        $codes = $this->freshCodes($quantity, $prefix);
        $now = now();

        return DB::transaction(function () use ($admin, $name, $quantity, $coinAmount, $codes, $now) {
            $batch = CoinCodeBatch::query()->create([
                'name' => $name,
                'quantity' => $quantity,
                'coin_amount' => $coinAmount,
                'created_by' => $admin->id,
            ]);

            foreach (array_chunk($codes, 200) as $chunk) {
                CoinCode::query()->insert(array_map(fn (string $code) => [
                    'code' => $code,
                    'batch_id' => $batch->id,
                    'coin_amount' => $coinAmount,
                    'status' => CoinCodeStatus::Unused->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            $this->audit->write('code.issued', $batch, null, [
                'quantity' => $quantity,
                'coin_amount' => $coinAmount,
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

            $taken = CoinCode::query()->whereIn('code', array_keys($codes))->pluck('code');

            foreach ($taken as $code) {
                unset($codes[$code]);
            }
        }

        return array_values($codes);
    }
}
