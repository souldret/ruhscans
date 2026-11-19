<?php

namespace App\Http\Middleware;

use App\Services\Security\HotlinkGuard;
use App\Services\Security\ReadRateGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePageReads
{
    public function __construct(
        private readonly ReadRateGuard $rates,
        private readonly HotlinkGuard $hotlink,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hotlink->allowed($request)) {
            abort(403, 'Bu adres başka bir siteden çağrılamaz.');
        }

        if ($this->rates->hit($request)) {
            return response()->json([
                'message' => 'Okuma hızı aşıldı. Kısa süre sonra yeniden deneyin.',
            ], 429);
        }

        return $next($request);
    }
}
