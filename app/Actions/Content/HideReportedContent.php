<?php

namespace App\Actions\Content;

use App\Enums\ReportStatus;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\Series;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HideReportedContent
{
    public function __invoke(Report $report, User $reviewer): void
    {
        DB::transaction(function () use ($report, $reviewer) {
            $target = $report->reportable;

            if ($target instanceof Series) {
                $target->update(['hidden' => true]);
            } elseif ($target instanceof Chapter) {
                $target->series()->update(['hidden' => true]);
                $target->delete();
            } else {
                throw new InvalidArgumentException('Şikayet edilen içerik gizlenemiyor.');
            }

            $report->update([
                'status' => ReportStatus::Incelendi,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
        });
    }
}
