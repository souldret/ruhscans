<?php

namespace App\Services\Launcher;

use App\Mail\SuspiciousLoginMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class LoginThrottle
{
    public function blocked(?User $user, string $ip): ?string
    {
        $this->pause($user, $ip);

        if ($user instanceof User && Cache::has($this->lockKey($user))) {
            $until = (int) Cache::get($this->lockKey($user));
            $left = max(1, $until - time());

            return 'Çok fazla hatalı deneme. Bu hesap '.$left.' saniye boyunca geçici olarak kilitli. Şifreni unuttuysan sıfırla; hesap kapatılmadı.';
        }

        return null;
    }

    /**
     * Hatalı denemeyi kaydeder. Eşik bu denemeyle aşıldıysa kilit mesajını döner.
     */
    public function recordFailure(?User $user, string $ip): ?string
    {
        $this->hitFailure($user, $ip);

        return $this->lockMessage($user);
    }

    public function hitFailure(?User $user, string $ip): void
    {
        $cfg = config('launcher.login');
        RateLimiter::hit($this->ipKey($ip), 60);
        RateLimiter::hit($this->delayKey($ip, $user), 900);

        if (! $user instanceof User) {
            return;
        }

        $attempts = RateLimiter::hit($this->accountKey($user), 60);

        if ($attempts >= (int) $cfg['lock_after']) {
            Cache::put($this->lockKey($user), time() + (int) $cfg['lock_seconds'], (int) $cfg['lock_seconds']);
        }

        if ($attempts >= (int) $cfg['alert_after']) {
            Mail::to($user->email)->send(new SuspiciousLoginMail($ip));
        }
    }

    public function clear(User $user, string $ip): void
    {
        RateLimiter::clear($this->accountKey($user));
        RateLimiter::clear($this->delayKey($ip, $user));
        Cache::forget($this->lockKey($user));
    }

    public function tooMany(string $ip, ?User $user): bool
    {
        $cfg = config('launcher.login');
        $account = (int) $cfg['account_per_minute'];
        $ipLimit = (int) $cfg['ip_per_minute'];

        if (RateLimiter::tooManyAttempts($this->ipKey($ip), $ipLimit)) {
            return true;
        }

        return $user instanceof User
            && RateLimiter::tooManyAttempts($this->accountKey($user), $account);
    }

    private function lockMessage(?User $user): ?string
    {
        if (! $user instanceof User || ! Cache::has($this->lockKey($user))) {
            return null;
        }

        $left = max(1, (int) Cache::get($this->lockKey($user)) - time());

        return 'Çok fazla hatalı deneme. Bu hesap '.$left.' saniye boyunca geçici olarak kilitli. Şifreni unuttuysan sıfırla; hesap kapatılmadı.';
    }

    private function pause(?User $user, string $ip): void
    {
        $cfg = config('launcher.login');
        $hits = RateLimiter::attempts($this->delayKey($ip, $user));

        if ($hits < 1) {
            return;
        }

        $ms = min((int) $cfg['max_delay_ms'], $hits * (int) $cfg['delay_step_ms']);

        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }

    private function ipKey(string $ip): string
    {
        return 'launcher-login-ip:'.sha1($ip);
    }

    private function accountKey(User $user): string
    {
        return 'launcher-login-acct:'.$user->id;
    }

    private function delayKey(string $ip, ?User $user): string
    {
        return 'launcher-login-delay:'.sha1($ip.'|'.($user?->id ?? '0'));
    }

    private function lockKey(User $user): string
    {
        return 'launcher-login-lock:'.$user->id;
    }
}
