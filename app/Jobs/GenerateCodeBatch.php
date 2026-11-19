<?php

namespace App\Jobs;

use App\Enums\CodeKind;
use App\Models\CodeBatch;
use App\Services\Economy\CodeExportStore;
use App\Services\Economy\CodeGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class GenerateCodeBatch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(
        public int $batchId,
    ) {}

    public function handle(CodeGenerator $generator, CodeExportStore $store): void
    {
        $batch = CodeBatch::query()->findOrFail($this->batchId);

        if ($batch->codes()->exists()) {
            return;
        }

        $kind = $batch->tur instanceof CodeKind ? $batch->tur : CodeKind::from((string) $batch->tur);
 $expires = $batch->son_kullanma instanceof Carbon ? $batch->son_kullanma : null;

 $result = $generator->fill($batch, (int) $batch->adet, $kind, (int) $batch->deger, $expires);

 $store->stash($batch, $generator->csv($result['codes']));
    }
}
