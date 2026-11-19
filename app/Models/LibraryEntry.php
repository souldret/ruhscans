<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryEntry extends Model
{
    protected $fillable = [
        'user_id',
        'series_id',
        'rating',
        'last_chapter_id',
        'last_page_order',
        'completed',
        'last_read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'last_page_order' => 'integer',
            'completed' => 'boolean',
            'last_read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function lastChapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'last_chapter_id');
    }
}
