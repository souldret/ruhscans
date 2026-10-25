<?php

namespace App\Models;

use Database\Factories\DownloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'game_file_id',
        'ip',
        'country',
        'day',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'date',
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
     * @return BelongsTo<GameFile, $this>
     */
    public function gameFile(): BelongsTo
    {
        return $this->belongsTo(GameFile::class);
    }
}
