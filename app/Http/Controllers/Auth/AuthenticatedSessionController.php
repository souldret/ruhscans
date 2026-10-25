<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SessionLimiter;
use App\Services\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, Turnstile $turnstile, SessionLimiter $sessions, AuditLogger $audit): RedirectResponse
    {
        if (! $turnstile->passes($request->string('cf-turnstile-response')->toString(), $request->ip())) {
            return back()->withInput()->withErrors(['email' => 'Doğrulama tamamlanmadı.']);
        }

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user instanceof User) {
            $sessions->enforce($user, $request->session()->getId());

            if ($user->isAdmin()) {
                $audit->write('admin.login', $user, null, [], $user);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
