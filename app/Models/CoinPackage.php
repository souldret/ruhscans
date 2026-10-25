<?php

namespace App\Models;

use App\Services\AuditLogger;
use Database\Factories\CoinPackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinPackage extends Model
{
    /** @use HasFactory<CoinPackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'coin_amount',
        'bonus_coin',
        'price_label',
        'url',
        'button_text',
        'badge',
        'description',
        'sort_order',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'coin_amount' => 'integer',
            'bonus_coin' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CoinPackageClick, $this>
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(CoinPackageClick::class);
    }

    public function totalCoins(): int
    {
        return $this->coin_amount + $this->bonus_coin;
    }

    protected static function booted(): void
    {
        static::saved(function (CoinPackage $package): void {
            if (! $package->wasRecentlyCreated && ! $package->wasChanged()) {
                return;
            }

            app(AuditLogger::class)->write(
                $package->wasRecentlyCreated ? 'package.created' : 'package.updated',
                $package,
                null,
                $package->only(['name', 'coin_amount', 'bonus_coin', 'price_label', 'url', 'is_active', 'is_featured']),
            );
        });
    }
}
