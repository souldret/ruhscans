<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->getRawOriginal('two_factor_confirmed_at') !== null) {
            return $next($request);
        }

        if ($request->is('yonetim/two-factor', 'yonetim/two-factor/*', 'yonetim/logout')) {
            return $next($request);
        }

        return redirect('/yonetim/two-factor');
    }
}
