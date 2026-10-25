<?php

namespace App\Models;

use App\Enums\CoinCodeStatus;
use Database\Factories\CoinCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinCode extends Model
{
    /** @use HasFactory<CoinCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'batch_id',
        'coin_amount',
        'status',
        'used_by',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'coin_amount' => 'integer',
            'status' => CoinCodeStatus::class,
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CoinCodeBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CoinCodeBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
