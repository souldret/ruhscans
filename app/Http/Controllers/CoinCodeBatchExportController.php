<?php

namespace App\Http\Controllers;

use App\Models\CoinCodeBatch;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoinCodeBatchExportController extends Controller
{
    public function __invoke(CoinCodeBatch $batch, string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['txt', 'csv'], true), 404);
        $this->authorize('view', $batch);

        $filename = 'coin-parti-'.$batch->id.'.'.$format;

        return response()->streamDownload(function () use ($batch, $format): void {
            $handle = fopen('php://output', 'w');
            abort_unless(is_resource($handle), 500);

            if ($format === 'csv') {
                fputcsv($handle, ['code', 'coin_amount', 'status']);
            }

            $batch->codes()->orderBy('id')->chunk(200, function ($codes) use ($handle, $format): void {
                foreach ($codes as $code) {
                    if ($format === 'csv') {
                        fputcsv($handle, [$code->code, $code->coin_amount, $code->status->value]);
                    } else {
                        fwrite($handle, $code->code."\n");
                    }
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => $format === 'csv' ? 'text/csv' : 'text/plain',
        ]);
    }
}
