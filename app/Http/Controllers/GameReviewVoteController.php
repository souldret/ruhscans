<?php

namespace App\Http\Controllers;

use App\Models\GameReview;
use App\Models\GameReviewVote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameReviewVoteController extends Controller
{
    public function store(Request $request, GameReview $review): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $game = $review->game;
        abort_unless($game?->is_published, 404);

        if ($review->user_id === $user->id) {
            return back()->with('review', 'Kendi yorumunu oylayamazsın.');
        }

        $existing = GameReviewVote::query()
            ->where('game_review_id', $review->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing instanceof GameReviewVote) {
            $existing->delete();

            return back()->with('review', 'Faydalı oyu geri alındı.');
        }

        GameReviewVote::query()->create([
            'game_review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        return back()->with('review', 'Faydalı olarak işaretlendi.');
    }
}
