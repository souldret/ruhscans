<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Turnstile
{
    public function enabled(): bool
    {
        return $this->siteKey() !== '' && $this->secret() !== '';
    }

    public function siteKey(): string
    {
        return $this->value('TURNSTILE_SITE_KEY');
    }

    public function secret(): string
    {
        return $this->value('TURNSTILE_SECRET_KEY');
    }

    private function value(string $key): string
    {
        $stored = $this->stored();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        $fromConfig = config($key === 'TURNSTILE_SITE_KEY' ? 'membership.turnstile_site' : 'membership.turnstile_secret');

        return is_string($fromConfig) ? trim($fromConfig) : '';
    }

    /**
     * @return array<string, string>
     */
    public function stored(): array
    {
        $path = storage_path('app/private/turnstile.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        return [
            'TURNSTILE_SITE_KEY' => trim((string) ($decoded['site'] ?? '')),
            'TURNSTILE_SECRET_KEY' => trim((string) ($decoded['secret'] ?? '')),
        ];
    }

    public function store(string $site, string $secret): void
    {
        $path = storage_path('app/private/turnstile.json');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode([
            'site' => $site,
            'secret' => $secret,
        ], JSON_THROW_ON_ERROR));

        $this->guard($directory);
    }

    private function guard(string $directory): void
    {
        $rules = $directory.DIRECTORY_SEPARATOR.'.htaccess';

        if (! is_file($rules)) {
            file_put_contents($rules, "Require all denied\n");
        }
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
                'secret' => $this->secret(),
                'response' => $token,
                'remoteip' => $ip,
            ]);

        return $response->ok() && $response->json('success') === true;
    }
}
