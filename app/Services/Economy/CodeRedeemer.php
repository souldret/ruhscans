<?php

namespace App\Services\Economy;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Exceptions\Economy\CodeRedeemException;
use App\Models\RedeemCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class CodeRedeemer
{
    public function __construct(
        private readonly CodeHasher $hasher,
        private readonly WalletService $wallet,
        private readonly VipService $vip,
    ) {}

    /**
     * @return array{tur: string, deger: int, bakiye: int, vip_bitis: ?string}
     */
    public function redeem(User $user, string $plain, string $ip): array
    {
        $this->guard($user, $ip);

        try {
            $result = $this->apply($user, $plain);
        } catch (CodeRedeemException $exception) {
            $this->recordFailure($user, $ip, $plain);

            throw $exception;
        }

        $this->clearFailures($user, $ip);
        $this->hitAttempt($user, $ip);

        return $result;
    }

    /**
     * @return array{tur: string, deger: int, bakiye: int, vip_bitis: ?string}
     */
    private function apply(User $user, string $plain): array
    {
        $normalized = $this->hasher->normalize($plain);

        if (! preg_match('/^RUH[2-9A-HJKMNP-Z]{16}$/', $normalized)) {
            throw CodeRedeemException::generic();
        }

        $hash = $this->hasher->hash($normalized);

        return DB::transaction(function () use ($user, $hash) {
            $code = RedeemCode::query()->where('kod_hash', $hash)->lockForUpdate()->first();

            if ($code === null) {
                throw CodeRedeemException::generic();
            }

            if ($code->durum !== CodeStatus::Aktif) {
                throw CodeRedeemException::generic();
            }

            if ($code->son_kullanma !== null && $code->son_kullanma->isPast()) {
 throw CodeRedeemException::generic();
 }

 $claimed = RedeemCode::query()
 ->whereKey($code->id)
 ->where('durum', CodeStatus::Aktif->value)
 ->where(function ($query) {
 $query->whereNull('son_kullanma')->orWhere('son_kullanma', '>', now());
 })
 ->update([
 'durum' => CodeStatus::Kullanildi->value,
 'kullanan_user_id' => $user->id,
 'kullanim_tarihi' => now(),
 ]);

 if ($claimed !== 1) {
 throw CodeRedeemException::generic();
 }

 $code->durum = CodeStatus::Kullanildi;
 $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($code->tur === CodeKind::RuhTasi) {
                $this->wallet->credit(
                    $lockedUser,
                    (int) $code->deger,
                    'kod',
                    'kod:'.$code->id,
                    RedeemCode::class,
                    $code->id,
                );
            } else {
                $this->vip->extend($lockedUser, (int) $code->deger);
            }

            $user->ruh_tasi_bakiye = $lockedUser->ruh_tasi_bakiye;
            $user->vip_bitis = $lockedUser->vip_bitis;

            return [
                'tur' => $code->tur->value,
                'deger' => (int) $code->deger,
                'bakiye' => (int) $lockedUser->ruh_tasi_bakiye,
                'vip_bitis' => $lockedUser->vip_bitis?->toIso8601String(),
            ];
        });
    }

    private function guard(User $user, string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->lockKey($user, $ip), 1)) {
            throw CodeRedeemException::locked();
        }

        if (RateLimiter::tooManyAttempts($this->attemptKey($user, $ip), (int) config('economy.redeem_attempts_per_minute'))) {
            throw CodeRedeemException::throttled();
        }
    }

    private function recordFailure(User $user, string $ip, string $plain): void
    {
        $this->hitAttempt($user, $ip);

        $failures = RateLimiter::hit(
            $this->failureKey($user, $ip),
            (int) config('economy.redeem_lock_minutes') * 60,
        );

        Log::warning('Kod kullanımı reddedildi.', [
            'user_id' => $user->id,
            'ip' => $ip,
            'son_dort' => $this->mask($plain),
        ]);

        if ($failures >= (int) config('economy.redeem_failures_before_lock')) {
            RateLimiter::hit($this->lockKey($user, $ip), (int) config('economy.redeem_lock_minutes') * 60);
        }
    }

    private function clearFailures(User $user, string $ip): void
    {
        RateLimiter::clear($this->failureKey($user, $ip));
        RateLimiter::clear($this->lockKey($user, $ip));
    }

    private function hitAttempt(User $user, string $ip): void
    {
        RateLimiter::hit($this->attemptKey($user, $ip), 60);
    }

    private function mask(string $plain): string
    {
        $normalized = $this->hasher->normalize($plain);

        if (strlen($normalized) < 4) {
            return '****';
        }

        return '****'.substr($normalized, -4);
    }

    private function attemptKey(User $user, string $ip): string
    {
        return 'kod-deneme:'.$user->id.':'.$ip;
    }

    private function failureKey(User $user, string $ip): string
    {
        return 'kod-hata:'.$user->id.':'.$ip;
    }

    private function lockKey(User $user, string $ip): string
    {
        return 'kod-kilit:'.$user->id.':'.$ip;
    }
}
