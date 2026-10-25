<?php

namespace App\Services;

use App\Enums\CoinCodeStatus;
use App\Enums\CoinTransactionType;
use App\Models\CoinCode;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Satış iadesi: kodu kapatır, yüklenen coin'i geri almaya çalışır.
 */
class CoinClawback
{
    public function __construct(
        private readonly CoinService $coins,
        private readonly AuditLogger $audit,
        private readonly AdminAlerter $alerts,
    ) {}

    public function disable(User $actor, CoinCode $code, string $reason): void
    {
        $reason = $this->reason($reason);

        DB::transaction(function () use ($actor, $code, $reason): void {
            $locked = CoinCode::query()->whereKey($code->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === CoinCodeStatus::Disabled) {
                return;
            }

            $locked->forceFill(['status' => CoinCodeStatus::Disabled])->save();

            $this->audit->write('code.disabled', $locked, $reason, [
                'coin_amount' => $locked->coin_amount,
            ], $actor);
        });
    }

    public function reclaim(User $actor, CoinCode $code, string $reason): string
    {
        $reason = $this->reason($reason);

        return DB::transaction(function () use ($actor, $code, $reason): string {
            $locked = CoinCode::query()->whereKey($code->id)->lockForUpdate()->firstOrFail();
            $key = 'coin-clawback:'.$locked->id;
            $wasUsed = $locked->used_by !== null;

            if (CoinTransaction::query()->where('idempotency_key', $key)->exists()) {
                return 'reclaimed';
            }

            if ($locked->status !== CoinCodeStatus::Disabled) {
                $locked->forceFill(['status' => CoinCodeStatus::Disabled])->save();
                $this->audit->write('code.disabled', $locked, $reason, [
                    'coin_amount' => $locked->coin_amount,
                ], $actor);
            }

            if (! $wasUsed) {
                return 'disabled';
            }

            $member = User::query()->whereKey($locked->used_by)->lockForUpdate()->first();

            if (! $member instanceof User) {
                return 'disabled';
            }

            $short = $member->coin_balance < $locked->coin_amount;
            $policy = (string) config('coins.clawback', 'review');

            if ($short && $policy !== 'negative') {
                $member->forceFill(['coin_review' => true])->save();
                $this->audit->write('code.clawback_review', $locked, $reason, [
                    'user_id' => $member->id,
                    'coin_amount' => $locked->coin_amount,
                    'balance' => $member->coin_balance,
                ], $actor);
                $this->alerts->send(
                    'coin-clawback:'.$locked->id,
                    'Coin iadesi inceleme bekliyor',
                    $member->email.' bakiyesi '.$locked->code.' kodunu karşılamıyor.',
                );

                return 'review';
            }

            $transaction = $this->coins->debit(
                $member,
                $locked->coin_amount,
                CoinTransactionType::Clawback,
                ModelRef::of($locked),
                $reason,
                $key,
                $actor,
                $policy === 'negative',
            );

            if ($policy === 'negative' && (int) $transaction->balance_after < 0) {
                $member->forceFill(['coin_review' => true])->save();
            }

            $this->audit->write('code.clawback', $locked, $reason, [
                'user_id' => $member->id,
                'coin_amount' => $locked->coin_amount,
                'balance_after' => $transaction->balance_after,
            ], $actor);

            return 'reclaimed';
        });
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Sebep zorunlu.');
        }

        return $reason;
    }
}
