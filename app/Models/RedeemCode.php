<?php

namespace App\Models;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedeemCode extends Model
{
    protected $fillable = [
        'batch_id',
        'tur',
        'deger',
        'son_kullanma',
        'durum',
        'kod_hash',
        'son_dort',
        'kullanan_user_id',
        'kullanim_tarihi',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tur' => CodeKind::class,
            'deger' => 'integer',
            'son_kullanma' => 'datetime',
            'durum' => CodeStatus::class,
            'kullanim_tarihi' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CodeBatch::class, 'batch_id');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kullanan_user_id');
    }
}
