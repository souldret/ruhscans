<?php

namespace App\Services;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\User;
use InvalidArgumentException;

/**
 * Yönetici bakiyesi. Sebep zorunlu, hareket deftere ve denetime yazılır.
 */
class CoinAdjuster
{
    public function __construct(
        private readonly CoinService $coins,
        private readonly AuditLogger $audit,
    ) {}

    public function adjust(User $actor, User $member, int $amount, string $reason): CoinTransaction
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Sebep zorunlu.');
        }

        if ($amount === 0) {
            throw new InvalidArgumentException('Coin tutarı sıfır olamaz.');
        }

        $transaction = $amount > 0
            ? $this->coins->credit($member, $amount, CoinTransactionType::AdminAdjust, null, $reason, null, $actor)
            : $this->coins->debit($member, abs($amount), CoinTransactionType::AdminAdjust, null, $reason, null, $actor);

        $this->audit->write('coin.adjusted', $member, $reason, [
            'amount' => $amount,
            'balance_after' => $transaction->balance_after,
        ], $actor);

        return $transaction;
    }
}
