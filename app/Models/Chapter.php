<?php

namespace App\Models;

use Database\Factories\ChapterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Chapter extends Model
{
    /** @use HasFactory<ChapterFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'series_id',
        'number',
        'title',
        'published_at',
        'ruh_tasi_fiyat',
        'vip_ucretsiz',
        'erken_erisim_bitis',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'decimal:2',
            'published_at' => 'datetime',
            'ruh_tasi_fiyat' => 'integer',
            'vip_ucretsiz' => 'boolean',
            'erken_erisim_bitis' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class)->orderBy('order');
    }

    public function uploads(): HasMany
    {
        return $this->hasMany(ChapterUpload::class);
    }

    public function latestUpload(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChapterUpload::class)->latestOfMany();
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
