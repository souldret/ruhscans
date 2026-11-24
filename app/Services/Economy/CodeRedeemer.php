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
        $validFormat = (bool) preg_match('/^RUH[2-9A-HJKMNP-Z]{16}$/', $normalized);

        // Zamanlama farkını azaltmak için biçim geçersiz olsa bile sabit maliyetli HMAC hesaplanır.
        $hash = $this->hasher->hash($validFormat ? $normalized : 'RUH2222222222222222');

        if (! $validFormat) {
            throw CodeRedeemException::generic();
        }

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
        foreach ($this->lockKeys($user, $ip) as $key) {
            if (RateLimiter::tooManyAttempts($key, 1)) {
                throw CodeRedeemException::locked();
            }
        }

        $maxPerMinute = (int) config('economy.redeem_attempts_per_minute');

        foreach ($this->attemptKeys($user, $ip) as $key) {
            if (RateLimiter::tooManyAttempts($key, $maxPerMinute)) {
                throw CodeRedeemException::throttled();
            }
        }
    }

    private function recordFailure(User $user, string $ip, string $plain): void
    {
        $this->hitAttempt($user, $ip);

        $lockSeconds = (int) config('economy.redeem_lock_minutes') * 60;
        $threshold = (int) config('economy.redeem_failures_before_lock');

        $userFailures = RateLimiter::hit($this->userFailureKey($user), $lockSeconds);
        $ipFailures = RateLimiter::hit($this->ipFailureKey($ip), $lockSeconds);

        Log::warning('Kod kullanımı reddedildi.', [
            'user_id' => $user->id,
            'ip' => $ip,
            'son_dort' => $this->mask($plain),
        ]);

        if ($userFailures >= $threshold) {
            RateLimiter::hit($this->userLockKey($user), $lockSeconds);
        }

        if ($ipFailures >= $threshold) {
            RateLimiter::hit($this->ipLockKey($ip), $lockSeconds);
        }
    }

    private function clearFailures(User $user, string $ip): void
    {
        RateLimiter::clear($this->userFailureKey($user));
        RateLimiter::clear($this->userLockKey($user));
        RateLimiter::clear($this->ipFailureKey($ip));
        RateLimiter::clear($this->ipLockKey($ip));
    }

    private function hitAttempt(User $user, string $ip): void
    {
        foreach ($this->attemptKeys($user, $ip) as $key) {
            RateLimiter::hit($key, 60);
        }
    }

    private function mask(string $plain): string
    {
        $normalized = $this->hasher->normalize($plain);

        if (strlen($normalized) < 4) {
            return '****';
        }

        return '****'.substr($normalized, -4);
    }

    /**
     * @return list<string>
     */
    private function attemptKeys(User $user, string $ip): array
    {
        return ['kod-deneme:u:'.$user->id, 'kod-deneme:ip:'.$ip];
    }

    /**
     * @return list<string>
     */
    private function lockKeys(User $user, string $ip): array
    {
        return [$this->userLockKey($user), $this->ipLockKey($ip)];
    }

    private function userFailureKey(User $user): string
    {
        return 'kod-hata:u:'.$user->id;
    }

    private function ipFailureKey(string $ip): string
    {
        return 'kod-hata:ip:'.$ip;
    }

    private function userLockKey(User $user): string
    {
        return 'kod-kilit:u:'.$user->id;
    }

    private function ipLockKey(string $ip): string
    {
        return 'kod-kilit:ip:'.$ip;
    }
}
