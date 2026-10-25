<?php

namespace App\Services;

use App\Enums\VipCodeStatus;
use App\Models\CodeAttempt;
use App\Models\User;
use App\Models\VipCode;
use App\Support\VipCodeNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VipCodeRedeemer
{
    public const FAILURE_MESSAGE = 'Kod geçersiz veya daha önce kullanılmış.';

    public function redeem(User $user, string $rawCode, ?string $ip): Carbon
    {
        $this->assertNotLocked($user, $ip);
        $this->hitRateLimit($user, $ip);

        $code = VipCodeNormalizer::normalize($rawCode);

        try {
            $until = DB::transaction(function () use ($user, $code): Carbon {
                $vipCode = VipCode::query()->where('code', $code)->lockForUpdate()->first();

                if ($vipCode === null || $vipCode->status !== VipCodeStatus::Unused) {
                    throw ValidationException::withMessages(['code' => self::FAILURE_MESSAGE]);
                }

                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $current = $lockedUser->vip_until;
                $start = $current instanceof Carbon && $current->isFuture()
                    ? $current->copy()
                    : now();
                $until = $start->copy()->addDays($vipCode->duration_days);

                $lockedUser->forceFill(['vip_until' => $until])->save();

                $vipCode->forceFill([
                    'status' => VipCodeStatus::Used,
                    'used_by' => $lockedUser->id,
                    'used_at' => now(),
                ])->save();

                return $until;
            });
        } catch (ValidationException $exception) {
            $this->recordFailure($user, $ip);

            throw $exception;
        }

        return $until;
    }

    public function assertNotLocked(User $user, ?string $ip): void
    {
        if (Cache::has($this->lockKey($user, $ip))) {
            throw ValidationException::withMessages([
                'code' => 'Çok fazla hatalı deneme. Bir saat sonra yeniden dene.',
            ]);
        }
    }

    public function recordFailure(User $user, ?string $ip): void
    {
        CodeAttempt::query()->create([
            'user_id' => $user->id,
            'ip' => $ip,
        ]);

        $since = now()->subHour();
        $byUser = CodeAttempt::query()->where('user_id', $user->id)->where('created_at', '>=', $since)->count();
        $byIp = $ip
            ? CodeAttempt::query()->where('ip', $ip)->where('created_at', '>=', $since)->count()
            : 0;

        if ($byUser >= 20 || $byIp >= 20) {
            Cache::put($this->lockKey($user, $ip), true, now()->addHour());
        }
    }

    private function hitRateLimit(User $user, ?string $ip): void
    {
        $key = 'vip-code-redeem:'.$user->id.':'.($ip ?: 'unknown');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Çok hızlı deniyorsun. Bir dakika bekle.',
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    private function lockKey(User $user, ?string $ip): string
    {
        return 'vip-code-lock:'.$user->id.':'.($ip ?: 'unknown');
    }
}
