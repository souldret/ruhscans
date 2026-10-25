<?php

namespace App\Http\Controllers;

use App\Models\CoinPackage;
use App\Models\CoinPackageClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CoinPackageController extends Controller
{
    public function index(): View
    {
        $packages = CoinPackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('coins.index', [
            'featured' => $packages->firstWhere('is_featured', true),
            'packages' => $packages->where('is_featured', false)->values(),
            'user' => auth()->user(),
        ]);
    }

    public function go(CoinPackage $package): RedirectResponse
    {
        abort_unless($package->is_active, 404);
        abort_unless(str_starts_with($package->url, 'https://'), 404);

        CoinPackageClick::query()->create([
            'coin_package_id' => $package->id,
            'user_id' => auth()->id(),
            'ip_hash' => hash('sha256', (string) request()->ip()),
        ]);

        return redirect()->away($package->url);
    }
}
