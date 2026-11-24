<?php

namespace App\Http\Middleware;

use App\Services\Profile\SessionManager;
use App\Services\Security\ClientAddress;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    public function __construct(
        private readonly SessionManager $sessions,
        private readonly ClientAddress $clientAddress,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->hasSession()) {
            $sessionId = $request->session()->getId();
            $trackedId = $request->session()->get('session_tracked_id');
            $trackedUserId = $request->session()->get('session_tracked_user_id');
            $lastTracked = (int) $request->session()->get('session_tracked_at', 0);

            $isSameTrackedSession = $trackedId === $sessionId && (int) $trackedUserId === (int) $user->id;

            if ($isSameTrackedSession && ! $user->trackedSessions()->whereKey($sessionId)->exists()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Bu oturum başka bir cihazdan kapatıldı.');
            }

            if (! $isSameTrackedSession || (time() - $lastTracked) >= 60) {
                $this->sessions->record(
                    $user,
                    $sessionId,
                    $this->clientAddress->ip($request),
                    $request->userAgent(),
                );
                $request->session()->put('session_tracked_id', $sessionId);
                $request->session()->put('session_tracked_user_id', $user->id);
                $request->session()->put('session_tracked_at', time());
            }
        }

        return $next($request);
    }
}
