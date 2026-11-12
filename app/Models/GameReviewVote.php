<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameReviewVote extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'game_review_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<GameReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(GameReview::class, 'game_review_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
