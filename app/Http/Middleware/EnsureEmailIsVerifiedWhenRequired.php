<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedWhenRequired
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (! config('membership.verification')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || ! $user->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? abort(403, 'E-posta adresin henüz doğrulanmadı.')
                : redirect()->guest(route($redirectToRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
