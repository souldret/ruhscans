<?php

namespace App\Models;

use Database\Factories\CoinCodeBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinCodeBatch extends Model
{
    /** @use HasFactory<CoinCodeBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'quantity',
        'coin_amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'coin_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CoinCode, $this>
     */
    public function codes(): HasMany
    {
        return $this->hasMany(CoinCode::class, 'batch_id');
    }
}
