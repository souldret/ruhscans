<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientCoinsException;
use App\Models\Game;
use App\Models\User;
use App\Services\GameUnlocker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameUnlockController extends Controller
{
    public function store(Request $request, Game $game, GameUnlocker $unlocker): JsonResponse|RedirectResponse
    {
        abort_unless($game->is_published, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($request->has('price') || $request->has('coin_price')) {
            abort(422);
        }

        try {
            $unlock = $unlocker->unlock($user, $game);
        } catch (InsufficientCoinsException $exception) {
            $missing = $exception->needed - $exception->balance;

            if (! $request->expectsJson()) {
                return back()->with('coin_short', $missing);
            }

            return response()->json([
                'ok' => false,
                'balance' => $exception->balance,
                'price' => $exception->needed,
                'missing' => $missing,
            ], 422);
        }

        if (! $request->expectsJson()) {
            return back();
        }

        $fresh = $user->fresh();

        return response()->json([
            'ok' => true,
            'paid' => $unlock->coins_paid,
            'balance' => $fresh instanceof User ? $fresh->coin_balance : 0,
        ]);
    }
}
