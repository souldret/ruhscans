<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAdult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgeGateController extends Controller
{
    public function confirm(Request $request): RedirectResponse
    {
        $target = $this->safeIntendedUrl($request, $request->string('intended')->toString());

        return redirect()->to($target)->cookie(
            EnsureAdult::COOKIE,
            '1',
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }

    private function safeIntendedUrl(Request $request, string $candidate): string
    {
        $fallback = route('home');

        if ($candidate === '' || str_starts_with($candidate, '//') || str_contains($candidate, '\\')) {
            return $fallback;
        }

        // Göreli yol ise (/kesfet gibi) güvenli kabul et.
        if (str_starts_with($candidate, '/')) {
            return $candidate;
        }

        $parsed = parse_url($candidate);

        if ($parsed === false || isset($parsed['user']) || isset($parsed['pass'])) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = strtolower((string) ($parsed['host'] ?? ''));
        $port = $parsed['port'] ?? null;

        if ($scheme !== strtolower($request->getScheme()) || $host !== strtolower($request->getHost())) {
            return $fallback;
        }

        $requestPort = $request->getPort();
        $defaultPort = $scheme === 'https' ? 443 : 80;
        if ($port !== null && (int) $port !== (int) $requestPort && (int) $port !== $defaultPort) {
            return $fallback;
        }

        $path = (string) ($parsed['path'] ?? '/');
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';

        return $path.$query;
    }
}
