<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinPackageClick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'coin_package_id',
        'user_id',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CoinPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(CoinPackage::class, 'coin_package_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
