<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileService
{
    public function verify(?string $token, ?Request $request = null): bool
    {
        if (! config('turnstile.enabled')) {
            return true;
        }

        if (! is_string($token) || $token === '' || config('turnstile.secret_key') === '') {
            return false;
        }

        $cacheKey = 'turnstile:token:'.hash('sha256', $token);

        if (! Cache::add($cacheKey, true, (int) config('turnstile.token_ttl', 300))) {
            return false;
        }

        $request ??= request();
        $remoteIp = trim(explode(',', (string) $request->header('CF-Connecting-IP', $request->ip()))[0]);

        try {
            $response = Http::timeout((int) config('turnstile.timeout', 5))
                ->asForm()
                ->post((string) config('turnstile.verify_url'), [
                    'secret' => config('turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Turnstile doğrulama isteği başarısız', ['hata' => $exception->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Turnstile servisi hata döndürdü', ['durum' => $response->status()]);

            return false;
        }

        return $response->json('success') === true;
    }
}
