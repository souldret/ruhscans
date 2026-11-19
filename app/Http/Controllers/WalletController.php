<?php

namespace App\Http\Controllers;

use App\Exceptions\Economy\CodeRedeemException;
use App\Models\SiteSetting;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('wallet.show', [
            'user' => $user,
            'transactions' => WalletTransaction::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->paginate(20),
            'sonteklif' => SiteSetting::get('sonteklif_url', (string) config('economy.sonteklif_url')),
            'glow' => $request->boolean('parla'),
        ]);
    }
}
