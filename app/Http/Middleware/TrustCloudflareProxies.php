<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflareProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $trusted = self::trustedList();

        if ($trusted !== []) {
            $request->setTrustedProxies(
                $trusted,
                Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        $remoteAddr = (string) $request->server->get('REMOTE_ADDR', '');

        if ($remoteAddr !== '' && self::isTrustedProxy($remoteAddr)) {
            $cloudflare = trim(explode(',', (string) $request->headers->get('CF-Connecting-IP', ''))[0]);

            if ($cloudflare !== '' && filter_var($cloudflare, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $cloudflare);
            }
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    public static function trustedList(): array
    {
        $raw = (string) config('security.trusted_proxies', '');
        $list = array_values(array_filter(array_map('trim', explode(',', $raw)), fn (string $ip) => $ip !== '' && $ip !== '*'));

        if (app()->environment('local', 'testing')) {
            $list = array_values(array_unique([...$list, '127.0.0.1', '::1']));
        }

        return $list;
    }

    public static function isTrustedProxy(string $ip): bool
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $list = self::trustedList();

        if ($list === []) {
            return false;
        }

        return IpUtils::checkIp($ip, $list);
    }
}
