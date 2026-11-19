<?php

namespace App\Models;

use App\Enums\CodeKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeBatch extends Model
{
    protected $fillable = [
        'ad',
        'tur',
        'deger',
        'son_kullanma',
        'adet',
        'olusturan_user_id',
 'not',
 ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tur' => CodeKind::class,
            'deger' => 'integer',
            'adet' => 'integer',
            'son_kullanma' => 'datetime',
        ];
    }

    public function codes(): HasMany
 {
 return $this->hasMany(RedeemCode::class, 'batch_id');
 }

 public function exports(): HasMany
 {
 return $this->hasMany(CodeExport::class, 'batch_id');
 }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'olusturan_user_id');
    }

    public function usedCount(): int
    {
        return $this->codes()->where('durum', 'kullanildi')->count();
    }

    public function activeCount(): int
    {
        return $this->codes()->where('durum', 'aktif')->count();
    }

    public function cancelledCount(): int
    {
        return $this->codes()->where('durum', 'iptal')->count();
    }
}
