<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\Concerns\ResolvesAuthenticatedUser;
use App\Http\Requests\RedeemCoinCodeRequest;
use App\Models\GameUnlock;
use App\Services\CoinCodeRedeemer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function show(Request $request): View
    {
        $user = $this->authenticatedUser($request);
        $user->load([
            'redeemedCodes' => fn ($query) => $query->latest('used_at')->limit(8),
            'downloads' => fn ($query) => $query->with('gameFile.game')->latest()->limit(8),
        ]);

        return view('account.show', [
            'user' => $user,
            'transactions' => $user->coinTransactions()->latest('created_at')->paginate(15, ['*'], 'defter'),
            'unlocks' => GameUnlock::query()->where('user_id', $user->id)->with('game')->latest('unlocked_at')->get(),
            'codeCount' => $user->redeemedCodes()->count(),
            'downloadCount' => $user->downloads()->count(),
        ]);
    }

    public function redeem(RedeemCoinCodeRequest $request, CoinCodeRedeemer $redeemer): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        $before = $user->coin_balance;
        $balance = $redeemer->redeem($user, $request->string('code')->toString(), $request->ip());

        return redirect()
            ->route('account.show')
            ->with('coin_balance', $balance)
            ->with('coin_gained', $balance - $before)
            ->with('celebrate', true);
    }
}
