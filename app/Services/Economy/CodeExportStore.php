<?php

namespace App\Services\Economy;

use App\Models\CodeBatch;
use App\Models\CodeExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CodeExportStore
{
    public function stash(CodeBatch $batch, string $csv): CodeExport
    {
        $token = Str::random(48);
        $path = 'code-exports/'.$token.'.csv';

        Storage::disk('local')->put($path, $csv);

        return CodeExport::query()->create([
            'batch_id' => $batch->id,
            'token' => $token,
            'path' => $path,
            'expires_at' => now()->addHour(),
        ]);
    }

    public function pull(string $token): ?CodeExport
    {
        $export = CodeExport::query()->where('token', $token)->first();

        if ($export === null || $export->downloaded_at !== null || $export->expires_at->isPast()) {
            if ($export !== null) {
                $this->destroy($export);
            }

            return null;
        }

        return $export;
    }

    public function contents(CodeExport $export): string
    {
        return Storage::disk('local')->get($export->path) ?: '';
    }

    public function destroy(CodeExport $export): void
    {
        Storage::disk('local')->delete($export->path);
        $export->delete();
    }
}
