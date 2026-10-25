<?php

namespace App\Services;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoinsException;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bakiyenin değiştiği tek yer. Başka hiçbir kod users.coin_balance yazmaz.
 */
class CoinService
{
    public function credit(
        User $user,
        int $amount,
        CoinTransactionType $type,
        ?ModelRef $reference = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
        ?User $actor = null,
    ): CoinTransaction {
        return $this->move($user, $amount, $type, $reference, $note, $idempotencyKey, $actor);
    }

    public function debit(
        User $user,
        int $amount,
        CoinTransactionType $type,
        ?ModelRef $reference = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
        ?User $actor = null,
        bool $allowNegative = false,
    ): CoinTransaction {
        return $this->move($user, -$amount, $type, $reference, $note, $idempotencyKey, $actor, $allowNegative);
    }

    private function move(
        User $user,
        int $signedAmount,
        CoinTransactionType $type,
        ?ModelRef $reference,
        ?string $note,
        ?string $idempotencyKey,
        ?User $actor,
        bool $allowNegative = false,
    ): CoinTransaction {
        if ($signedAmount === 0) {
            throw new InvalidArgumentException('Coin tutarı sıfır olamaz.');
        }

        return DB::transaction(function () use ($user, $signedAmount, $type, $reference, $note, $idempotencyKey, $actor, $allowNegative) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = CoinTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

                if ($existing instanceof CoinTransaction) {
                    return $existing;
                }
            }

            $next = $locked->coin_balance + $signedAmount;

            if ($next < 0 && ! $allowNegative) {
                throw new InsufficientCoinsException($locked->coin_balance, abs($signedAmount));
            }

            $locked->forceFill(['coin_balance' => $next])->save();

            return CoinTransaction::query()->create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $signedAmount,
                'balance_after' => $next,
                'reference_type' => $reference?->type,
                'reference_id' => $reference?->id,
                'note' => $note,
                'created_by' => $actor?->id,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }
}
