<?php

namespace App\Http\Controllers;

use App\Exceptions\Economy\CodeRedeemException;
use App\Services\Economy\CodeRedeemer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedeemCodeController extends Controller
{
    public function store(Request $request, CodeRedeemer $redeemer): RedirectResponse
    {
        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:64'],
        ]);

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
