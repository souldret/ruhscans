<?php

namespace App\Models;

use Database\Factories\GameReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameReview extends Model
{
    /** @use HasFactory<GameReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'user_id',
        'positive',
        'body',
        'spoiler',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'positive' => 'boolean',
            'spoiler' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GameReviewVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(GameReviewVote::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
