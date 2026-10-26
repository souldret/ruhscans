<?php

namespace App\Models;

use App\Enums\Platform;
use Database\Factories\GameFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameFile extends Model
{
    /** @use HasFactory<GameFileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'label',
        'platform',
        'storage_path',
        'size_bytes',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

 public function sizeLabel(): string
 {
 $mb = $this->size_bytes / 1048576;

 if ($mb >= 1024) {
 return number_format($mb / 1024, 2, ',', '.').' GB';
 }

 if ($mb >= 1) {
 return number_format($mb, 0, ',', '.').' MB';
 }

 return number_format(max(0, $this->size_bytes) / 1024, 0, ',', '.').' KB';
 }

    /**
     * @return HasMany<Download, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }
}
