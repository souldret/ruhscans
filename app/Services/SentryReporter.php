<?php

namespace App\Services;

use App\Support\SecretRedactor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class SentryReporter
{
    public function capture(Throwable $exception): void
    {
        $dsn = config('observability.sentry_dsn');

        if (! is_string($dsn) || $dsn === '') {
            return;
        }

        $parts = parse_url($dsn);

        if (! is_array($parts) || ! isset($parts['host'], $parts['user'], $parts['path'])) {
            return;
        }

        $project = trim((string) $parts['path'], '/');
        $store = ($parts['scheme'] ?? 'https').'://'.$parts['host'].'/api/'.$project.'/store/';

        try {
            Http::timeout(2)
                ->withHeaders([
                    'X-Sentry-Auth' => 'Sentry sentry_version=7, sentry_key='.$parts['user'].', sentry_client=ruhscans/1.0',
                ])
                ->post($store, [
                    'event_id' => str_replace('-', '', (string) Str::uuid()),
                    'timestamp' => now()->toIso8601String(),
                    'platform' => 'php',
                    'level' => 'error',
                    'environment' => config('app.env'),
                    'release' => config('observability.release'),
                    'message' => $exception::class,
                    'extra' => SecretRedactor::redact([
                        'request_id' => request()->attributes->get('request_id'),
                    ]),
                ]);
        } catch (Throwable) {
            // Kapalı veya ulaşılamaz Sentry uygulamayı durdurmaz.
        }
    }
}
