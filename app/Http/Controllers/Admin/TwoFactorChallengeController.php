<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole('admin')) {
            abort(403);
        }

        if ($user->hasConfirmedTwoFactor()) {
            return redirect('/admin');
        }

        $secret = $request->session()->get('admin_2fa_secret');

        if (! is_string($secret) || $secret === '') {
            $secret = app(TwoFactorService::class)->generateSecret();
            $request->session()->put('admin_2fa_secret', $secret);
        }

        return view('admin.two-factor', [
            'secret' => $secret,
            'otpauth' => app(TwoFactorService::class)->qrCodeUrl($user, $secret),
        ]);
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $user->hasRole('admin'), 403);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = $request->session()->get('admin_2fa_secret');

        if (! is_string($secret) || ! $twoFactor->confirm($user, $secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Doğrulama kodu geçersiz.']);
        }

        $request->session()->forget('admin_2fa_secret');

        return redirect('/admin');
    }
}
