<?php

namespace App\Models;

use App\Enums\CoinTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CoinTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'type' => CoinTransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::updating(function (CoinTransaction $row): void {
            if ($row->exists) {
                throw new LogicException('Coin hareketi değiştirilemez.');
            }
        });
        static::deleting(function (CoinTransaction $row): void {
            if ($row->exists) {
                throw new LogicException('Coin hareketi silinemez.');
            }
        });
    }
}
