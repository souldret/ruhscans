<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request, TwoFactorService $twoFactor): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('admin'), 403);

        if ($user->hasConfirmedTwoFactor()) {
            return redirect('/admin');
        }

        $secret = $request->session()->get('admin_2fa_secret');

        if (! is_string($secret) || $secret === '') {
            $secret = $twoFactor->generateSecret();
            $request->session()->put('admin_2fa_secret', $secret);
        }

        return view('admin.two-factor', [
            'secret' => $secret,
            'otpauth' => $twoFactor->qrCodeUrl($user, $secret),
            'recoveryCodes' => null,
        ]);
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('admin'), 403);

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = $request->session()->get('admin_2fa_secret');

        if (! is_string($secret) || $secret === '') {
            return back()->withErrors(['code' => 'Kurulum süresi doldu. Sayfayı yenileyip yeniden başlayın.']);
        }

        $codes = $twoFactor->confirm($user, $secret, $validated['code']);

        if ($codes === null) {
            return back()->withErrors(['code' => 'Doğrulama kodu geçersiz.']);
        }

        $request->session()->forget('admin_2fa_secret');
        $request->session()->put('admin_2fa_passed', true);
        $request->session()->put('admin_2fa_recovery', $codes);

        return redirect()->route('admin.two-factor.recovery');
    }

    public function recovery(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $codes = $request->session()->pull('admin_2fa_recovery');

        if (! is_array($codes) || $codes === []) {
            return redirect('/admin');
        }

        return view('admin.two-factor-recovery', [
            'recoveryCodes' => $codes,
        ]);
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('admin') && $user->hasConfirmedTwoFactor(), 403);

        if ($request->session()->get('admin_2fa_passed') === true) {
            return redirect()->intended('/admin');
        }

        return view('admin.two-factor-challenge');
    }

    public function verify(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('admin') && $user->hasConfirmedTwoFactor(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $code = preg_replace('/\s+/', '', $validated['code']) ?? '';
        $secret = $user->two_factor_secret;
        $passed = is_string($secret) && preg_match('/^\d{6}$/', $code) === 1 && $twoFactor->verify($secret, $code);

        if (! $passed && $twoFactor->consumeRecoveryCode($user, $code)) {
            $passed = true;
        }

        if (! $passed) {
            return back()->withErrors(['code' => 'Kod veya kurtarma kodu geçersiz.']);
        }

        $request->session()->put('admin_2fa_passed', true);

        return redirect()->intended('/admin');
    }
}
