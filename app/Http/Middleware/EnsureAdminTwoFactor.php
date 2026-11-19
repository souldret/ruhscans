<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('admin')) {
            return $next($request);
        }

        if ($request->routeIs('admin.two-factor', 'admin.two-factor.store', 'admin.two-factor.recovery', 'admin.two-factor.challenge', 'admin.two-factor.verify')) {
            return $next($request);
        }

        if (! $user->hasConfirmedTwoFactor()) {
            if ($request->expectsJson()) {
                abort(403, 'İki adımlı doğrulama kurulmalı.');
            }

            return redirect()->route('admin.two-factor');
        }

        if ($request->session()->get('admin_2fa_passed') !== true) {
            if ($request->expectsJson()) {
                abort(403, 'İki adımlı doğrulama gerekli.');
            }

            return redirect()->guest(route('admin.two-factor.challenge'));
        }

        return $next($request);
    }
}
