<?php

namespace App\Http\Controllers;

use App\Support\AdultGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AdultGateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        return redirect()->to($this->safeBack($request))->withCookie($this->plainCookie($request, AdultGate::COOKIE, 60 * 24 * 365));
    }

    public function notice(Request $request): RedirectResponse
    {
        return redirect()->to($this->safeBack($request))->withCookie($this->plainCookie($request, 'notice', 60 * 24 * 365));
    }

    /**
     * Referer yalnızca aynı siteye aitse kullanılır; aksi halde ana sayfaya dönülür.
     */
    private function safeBack(Request $request): string
    {
        $referer = (string) $request->headers->get('referer', '');
        $host = parse_url($referer, PHP_URL_HOST);

        if ($referer === '' || ! is_string($host) || strcasecmp($host, $request->getHost()) !== 0) {
            return route('home');
        }

        return $referer;
    }

    private function plainCookie(Request $request, string $name, int $minutes): Cookie
    {
        return cookie()->make(
            $name,
            '1',
            $minutes,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
