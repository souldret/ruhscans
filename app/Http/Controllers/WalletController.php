<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\Ops\SiteOperations;
use App\Services\Seo\MetaBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function show(Request $request, SiteOperations $ops, MetaBuilder $meta): View
    {
        $user = $request->user();

        return view('wallet.show', [
            'user' => $user,
            'transactions' => WalletTransaction::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->paginate(20),
            'sonteklif' => $ops->sonteklifUrl(),
            'glow' => $request->boolean('parla'),
            'seo' => $meta->privatePage(
                'Cüzdan',
                'Ruh taşı bakiyen, VIP süren ve Sonteklif kodun. Bu sayfa arama motorlarına kapalıdır.',
            ),
        ]);
    }
}
