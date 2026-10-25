<?php

namespace App\Services;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\GameUnlock;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CoinAnomaly
{
    public function __construct(private readonly AdminAlerter $alerts) {}

    public function unlocks(User $user): void
    {
        $minutes = max(1, (int) config('coins.anomaly.unlock_minutes', 10));
        $limit = max(1, (int) config('coins.anomaly.unlock_count', 8));
        $count = GameUnlock::query()
            ->where('user_id', $user->id)
            ->where('unlocked_at', '>=', now()->subMinutes($minutes))
            ->count();

        if ($count < $limit) {
            return;
        }

        $this->alerts->send(
            'coin-unlocks:'.$user->id,
            'Çok sayıda oyun açma',
            $user->email.' '.$minutes.' dakikada '.$count.' oyun açtı.',
        );
    }

    public function registration(?string $ip): void
    {
        $this->burst(
            'signup',
            $ip,
            (int) config('coins.anomaly.signup_minutes', 10),
            (int) config('coins.anomaly.signup_count', 4),
            'Aynı IP’den çok sayıda yeni hesap',
        );
    }

    public function redemption(User $user, ?string $ip): void
    {
        $minutes = max(1, (int) config('coins.anomaly.redeem_minutes', 10));
        $limit = max(1, (int) config('coins.anomaly.redeem_count', 5));
        $count = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CoinTransactionType::CodeRedeem)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();

        if ($count >= $limit) {
            $this->alerts->send(
                'coin-redeem:'.$user->id,
                'Çok sayıda kod kullanımı',
                $user->email.' '.$minutes.' dakikada '.$count.' kod kullandı.',
            );
        }

        $this->burst('redeem', $ip, $minutes, $limit, 'Aynı IP’den çok sayıda kod kullanımı');
    }

    private function burst(string $kind, ?string $ip, int $minutes, int $limit, string $title): void
    {
        if (! is_string($ip) || $ip === '') {
            return;
        }

        $key = 'coin-burst:'.$kind.':'.hash('sha256', $ip);
        $minutes = max(1, $minutes);
        $limit = max(1, $limit);

        $count = (int) Cache::get($key, 0) + 1;
        Cache::put($key, $count, now()->addMinutes($minutes));

        if ($count < $limit) {
            return;
        }

        $this->alerts->send($key, $title, $ip.' adresinden '.$minutes.' dakikada '.$count.' işlem.');
    }
}
