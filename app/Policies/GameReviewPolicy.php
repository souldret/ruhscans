<?php

namespace App\Policies;

use App\Models\GameReview;
use App\Models\User;

class GameReviewPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, GameReview $review): bool
    {
        return $user->isAdmin() || $user->is($review->user);
    }
}
