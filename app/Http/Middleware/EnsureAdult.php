<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdult
{
    public const COOKIE = 'ruhscans_adult';

    /**
     * @var list<string>
     */
    private array $exempt = [
        'robots.txt',
        'sitemap',
        'sitemap.xml',
        'kullanim-sartlari',
        'gizlilik',
        'dmca',
        '18-uyari',
        'sikayet',
        'bildirim',
        'up',
        'admin',
        'admin/*',
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'reset-password/*',
        'verify-email',
        'verify-email/*',
        'confirm-password',
        'dashboard',
        'profile',
        'okuma-tercihleri',
        'horizon',
        'horizon/*',
        'livewire/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExempt($request) || $request->cookie(self::COOKIE) === '1') {
            return $next($request);
        }

        if ($request->isMethod('POST') && $request->routeIs('age-gate.confirm')) {
            return $next($request);
        }

        return response()
            ->view('age-gate', ['intended' => $request->fullUrl()])
            ->setStatusCode(403);
    }

    private function isExempt(Request $request): bool
    {
        foreach ($this->exempt as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
