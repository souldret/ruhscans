<?php

namespace App\Http\Controllers;

use App\Exceptions\Economy\CodeRedeemException;
use App\Services\Economy\CodeRedeemer;
use App\Services\Security\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedeemCodeController extends Controller
{
    public function store(Request $request, CodeRedeemer $redeemer, TurnstileService $turnstile): RedirectResponse
    {
        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:64'],
        ]);

        if (! $turnstile->verify($request->input('turnstile_token'), $request)) {
            return back()
                ->withInput()
                ->withErrors(['turnstile_token' => 'Doğrulama başarısız. Lütfen tekrar deneyin.']);
        }

        try {
            $redeemer->redeem($request->user(), $validated['kod'], (string) $request->ip());
        } catch (CodeRedeemException $exception) {
            return back()
                ->withInput()
                ->withErrors(['kod' => $exception->getMessage()]);
        }

        return redirect()
            ->route('wallet.show', ['parla' => 1])
            ->with('status', 'Kod kullanıldı.');
    }
}
