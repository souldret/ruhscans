<?php

namespace App\Actions\Legal;

use App\Enums\ReportStatus;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\Series;
use Illuminate\Support\Facades\DB;

class SubmitReport
{
    /**
     * @param  array{kind: string, email: string, reason: string, url?: string|null, series_slug?: string|null, chapter_number?: string|null}  $payload
     */
    public function handle(array $payload, ?int $userId): Report
    {
        return DB::transaction(function () use ($payload, $userId) {
            $target = $this->target($payload);

            return Report::query()->create([
                'user_id' => $userId,
                'reportable_type' => $target ? $target::class : null,
                'reportable_id' => $target?->getKey(),
                'reason' => $this->reason($payload),
                'status' => ReportStatus::Bekliyor,
            ]);
        });
    }

    /**
     * @param  array{series_slug?: string|null, chapter_number?: string|null}  $payload
     */
    private function target(array $payload): Series|Chapter|null
    {
        $slug = $payload['series_slug'] ?? null;

        if (! filled($slug)) {
            return null;
        }

        $series = Series::query()->where('slug', $slug)->first();

        if ($series === null) {
            return null;
        }

        $number = $payload['chapter_number'] ?? null;

        if (! filled($number)) {
            return $series;
        }

        return Chapter::query()
            ->where('series_id', $series->id)
            ->where('number', $number)
            ->first() ?? $series;
    }

    /**
     * @param  array{kind: string, email: string, reason: string, url?: string|null}  $payload
     */
    private function reason(array $payload): string
    {
        $kind = $payload['kind'] === 'dmca' ? 'DMCA' : 'Şikayet';
        $url = filled($payload['url'] ?? null) ? $payload['url'] : 'belirtilmedi';

        return $kind."\nİletişim: ".$payload['email']."\nAdres: ".$url."\n\n".$payload['reason'];
    }
}
