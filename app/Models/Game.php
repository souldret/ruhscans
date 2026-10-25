<?php

namespace App\Models;

use App\Enums\TranslationStatus;
use App\Jobs\MakeCoverVariants;
use App\Services\AuditLogger;
use App\Services\CatalogCache;
use App\Support\CounterBuffer;
use App\Support\Slug;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uploaded_by',
        'archive_disk',
        'engine',
        'title',
        'slug',
        'original_title',
        'developer',
        'released_on',
        'description',
        'cover_path',
        'banner_path',
        'screenshots',
        'game_version',
        'translation_version',
        'translation_status',
        'translation_percent',
        'size_mb',
        'playtime_hours',
        'apk_size_mb',
        'coin_price',
        'sale_price',
        'sale_ends_at',
        'is_published',
        'published_at',
        'view_count',
        'download_count',
        'search_text',
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
            'playtime_hours' => 'integer',
            'apk_size_mb' => 'integer',
            'released_on' => 'date',
            'coin_price' => 'integer',
            'sale_price' => 'integer',
            'sale_ends_at' => 'datetime',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function isFree(): bool
    {
        return $this->effectivePrice() === 0;
    }

    public function effectivePrice(): int
    {
        if ($this->sale_price !== null && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture())) {
            return $this->sale_price;
        }

        return $this->coin_price;
    }

    /**
     * @return HasMany<GamePriceHistory, $this>
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(GamePriceHistory::class);
    }

    /**
     * @return HasMany<GameUnlock, $this>
     */
    public function unlocks(): HasMany
    {
        return $this->hasMany(GameUnlock::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            $game->search_text = Slug::fold(trim($game->title.' '.$game->developer));

            if (! filled($game->title)) {
                return;
            }

            if ($game->isDirty('title') || blank($game->slug)) {
                $game->slug = static::uniqueSlug(Slug::from($game->title), $game->exists ? $game->id : null);
            }
        });

        static::updated(function (Game $game): void {
            if (! $game->wasChanged('coin_price')) {
                return;
            }

            GamePriceHistory::query()->create([
                'game_id' => $game->id,
                'old_price' => (int) $game->getOriginal('coin_price'),
                'new_price' => (int) $game->coin_price,
                'changed_by' => auth()->id(),
            ]);
            app(AuditLogger::class)->write('game.price_changed', $game, null, [
                'old_price' => (int) $game->getOriginal('coin_price'),
                'new_price' => (int) $game->coin_price,
            ]);
        });

        static::saved(function (Game $game): void {
            CatalogCache::flush();

            if ($game->wasChanged('cover_path') && filled($game->cover_path)) {
                MakeCoverVariants::dispatch($game->id);
            }
        });

        static::deleted(fn () => CatalogCache::flush());
    }

    /**
     * @param  Builder<Game>  $query
     * @return Builder<Game>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function recordView(?int $userId, ?string $ip): void
    {
        $who = $userId !== null ? 'user:'.$userId : 'ip:'.($ip ?: 'unknown');

        if (! Cache::add("game-view:{$this->id}:{$who}", true, now()->addHours(6))) {
            return;
        }

        app(CounterBuffer::class)->add('view_count', $this->id);
    }

    public static function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'oyun';
        $candidate = $base;
        $suffix = 2;

        while (static::query()
            ->withTrashed()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<GameFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(GameFile::class);
    }

    /**
     * @return HasMany<GameReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(GameReview::class);
    }
}
