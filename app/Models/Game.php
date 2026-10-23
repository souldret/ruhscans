<?php

namespace App\Models;

use App\Enums\GameAccess;
use App\Enums\TranslationStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'original_title',
        'developer',
        'description',
        'cover_path',
        'banner_path',
        'screenshots',
        'game_version',
        'translation_version',
        'translation_status',
        'translation_percent',
        'size_mb',
        'access',
        'is_published',
        'published_at',
        'view_count',
        'download_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'screenshots' => 'array',
            'translation_status' => TranslationStatus::class,
            'translation_percent' => 'integer',
            'size_mb' => 'integer',
            'access' => GameAccess::class,
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(GameFile::class);
    }
}
