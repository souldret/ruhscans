<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameReviewRequest;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class GameReviewController extends Controller
{
    public function store(StoreGameReviewRequest $request, Game $game): RedirectResponse
    {
        abort_unless($game->is_published, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($game, $user, $request): void {
            $game->reviews()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'positive' => $request->boolean('positive'),
                    'body' => $request->string('body')->toString(),
                ],
            );
        });

        return back()->with('review', 'Yorumun kaydedildi.');
    }

    public function destroy(GameReview $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        $review->delete();

        return back()->with('review', 'Yorum silindi.');
    }
}
