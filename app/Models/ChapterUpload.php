<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterUpload extends Model
{
    protected $fillable = [
        'chapter_id',
        'user_id',
        'status',
        'total',
        'processed',
        'failures',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'processed' => 'integer',
            'failures' => 'array',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return (int) floor(($this->processed / $this->total) * 100);
    }
}
