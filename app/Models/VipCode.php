<?php

namespace App\Models;

use App\Enums\VipCodeStatus;
use Database\Factories\VipCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipCode extends Model
{
    /** @use HasFactory<VipCodeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'batch_id',
        'duration_days',
        'status',
        'used_by',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'status' => VipCodeStatus::class,
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CodeBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CodeBatch::class, 'batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
