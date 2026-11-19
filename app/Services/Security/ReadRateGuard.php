<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class ReadRateGuard
{
    public function __construct(private readonly ClientAddress $address) {}

    public function blocked(?Request $request = null): bool
    {
        return Cache::has($this->blockKey($request));
    }

    public function hit(?Request $request = null): bool
    {
        $request ??= request();

        if ($this->blocked($request)) {
            return true;
        }

        $minute = (int) config('security.read_rate.per_minute', 40);
        $minuteKey = 'read-minute:'.$this->address->key($request);

        if (RateLimiter::tooManyAttempts($minuteKey, $minute)) {
            $this->block($request, 'dakika');

            return true;
        }

        RateLimiter::hit($minuteKey, 60);

        $window = max(1, (int) config('security.read_rate.burst_window_seconds', 10));
        $burstMax = (int) config('security.read_rate.burst_max', 12);
        $burstKey = 'read-burst:'.$this->address->key($request);
        $hits = (int) Cache::get($burstKey, 0) + 1;

        Cache::put($burstKey, $hits, now()->addSeconds($window));

        if ($hits > $burstMax) {
            $this->block($request, 'patlama');

            return true;
        }

        return false;
    }

    public function block(?Request $request = null, string $reason = 'patlama'): void
    {
        $minutes = max(1, (int) config('security.read_rate.block_minutes', 15));

        Cache::put($this->blockKey($request), [
            'reason' => $reason,
            'until' => now()->addMinutes($minutes)->toIso8601String(),
        ], now()->addMinutes($minutes));
    }

    private function blockKey(?Request $request): string
    {
        return 'read-block:'.$this->address->key($request);
    }
}
