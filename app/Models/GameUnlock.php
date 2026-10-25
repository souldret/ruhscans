<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameUnlock extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'unlocked_at';

    protected $fillable = [
        'user_id',
        'game_id',
        'coins_paid',
    ];

    protected function casts(): array
    {
        return [
            'coins_paid' => 'integer',
            'unlocked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
