<?php

namespace App\Services\Economy;

use App\Enums\LedgerDirection;
use App\Exceptions\Economy\InsufficientBalanceException;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * @param  class-string|null  $referenceType
     */
    public function credit(
        User $user,
        int $amount,
        string $reason,
        string $idempotencyKey,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        return $this->post($user, LedgerDirection::Kredi, $amount, $reason, $idempotencyKey, $referenceType, $referenceId);
    }

    /**
     * @param  class-string|null  $referenceType
     */
    public function debit(
        User $user,
        int $amount,
        string $reason,
        string $idempotencyKey,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): WalletTransaction {
        return $this->post($user, LedgerDirection::Borc, $amount, $reason, $idempotencyKey, $referenceType, $referenceId);
    }

    /**
     * @param  class-string|null  $referenceType
     */
    private function post(
        User $user,
        LedgerDirection $direction,
        int $amount,
        string $reason,
        string $idempotencyKey,
        ?string $referenceType,
        ?int $referenceId,
    ): WalletTransaction {
        if ($amount < 1) {
            throw new \InvalidArgumentException('Miktar en az 1 olmalı.');
        }

        return DB::transaction(function () use ($user, $direction, $amount, $reason, $idempotencyKey, $referenceType, $referenceId) {
            $existing = WalletTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $locked->ruh_tasi_bakiye;

            $next = $direction === LedgerDirection::Kredi
                ? $balance + $amount
                : $balance - $amount;

            if ($next < 0) {
                throw new InsufficientBalanceException('Ruh taşı bakiyesi yetersiz.');
            }

            User::$balanceWriteAllowed = true;

            try {
                $locked->forceFill(['ruh_tasi_bakiye' => $next])->save();
            } catch (QueryException $exception) {
                if ($this->isNonNegativeViolation($exception)) {
                    throw new InsufficientBalanceException('Ruh taşı bakiyesi yetersiz.');
                }

                throw $exception;
            } finally {
                User::$balanceWriteAllowed = false;
            }

            $user->ruh_tasi_bakiye = $next;

            return WalletTransaction::query()->create([
                'user_id' => $locked->id,
                'yon' => $direction,
                'miktar' => $amount,
                'bakiye_sonrasi' => $next,
                'sebep' => $reason,
                'referans_tip' => $referenceType,
                'referans_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    private function isNonNegativeViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'users_ruh_tasi_bakiye_nonneg')
            || str_contains($message, 'CHECK constraint failed');
    }
}
