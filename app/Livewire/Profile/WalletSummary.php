<?php

namespace App\Livewire\Profile;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class WalletSummary extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $transactions = $user->walletTransactions()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $unlocks = $user->chapterUnlocks()
            ->with(['chapter.series'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('livewire.profile.wallet-summary', [
            'user' => $user,
            'transactions' => $transactions,
            'unlocks' => $unlocks,
        ]);
    }
}
