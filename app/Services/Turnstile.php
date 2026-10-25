<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Turnstile
{
    public function enabled(): bool
    {
        $secret = config('membership.turnstile_secret');

        return is_string($secret) && $secret !== '';
    }

    public function passes(?string $token, ?string $ip): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! is_string($token) || $token === '') {
            return false;
        }

        $response = Http::asForm()
            ->timeout(5)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('membership.turnstile_secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

        return $response->ok() && $response->json('success') === true;
    }
}
