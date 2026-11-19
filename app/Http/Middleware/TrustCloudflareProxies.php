<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflareProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $proxies = config('security.trusted_proxies', '*');

        if ($proxies === '*' || $proxies === null || $proxies === '') {
            $request->setTrustedProxies(['*'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB);
        } else {
            $list = array_values(array_filter(array_map('trim', explode(',', (string) $proxies))));
            $request->setTrustedProxies($list, Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }

        $cloudflare = $request->headers->get('CF-Connecting-IP');

        if (is_string($cloudflare) && filter_var($cloudflare, FILTER_VALIDATE_IP)) {
            $request->server->set('REMOTE_ADDR', $cloudflare);
        }

        return $next($request);
    }
}
