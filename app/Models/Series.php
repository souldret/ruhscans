<?php

namespace App\Models;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use Database\Factories\SeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Series extends Model
{
    /** @use HasFactory<SeriesFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'translator_group_id',
        'title',
        'slug',
        'alternative_names',
        'description',
        'cover_path',
        'type',
        'status',
        'content_level',
        'year',
        'author',
        'artist',
        'hidden',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternative_names' => 'array',
            'type' => SeriesType::class,
            'status' => SeriesStatus::class,
            'content_level' => ContentLevel::class,
            'hidden' => 'boolean',
            'year' => 'integer',
        ];
    }

    public function translatorGroup(): BelongsTo
    {
        return $this->belongsTo(TranslatorGroup::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function libraryEntries(): HasMany
    {
        return $this->hasMany(LibraryEntry::class);
    }

    public function viewerLibrary(): HasOne
    {
        return $this->hasOne(LibraryEntry::class)->where('user_id', auth()->id());
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
