<?php

namespace App\Services\Content;

use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\Series;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class BulkChapterZipImporter
{
    /**
     * @param array{title?: ?string, ruh_tasi_fiyat?: int, vip_ucretsiz?: bool, published_at?: mixed, erken_erisim_bitis?: mixed} $defaults
     * @return list<int>
     */
    public function queue(Series $series, string $zipPath, array $defaults, ?int $userId = null): array
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Toplu ZIP açılamadı.');
        }

        $folders = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));

            if (str_contains($name, '__MACOSX') || str_starts_with(basename($name), '.')) {
                continue;
            }

            $parts = array_values(array_filter(explode('/', $name), fn (string $part) => $part !== ''));

            if (count($parts) < 2) {
                continue;
            }

            $folder = $parts[0];

            if (! preg_match('/^\d+(?:[.,]\d+)?$/', $folder)) {
                continue;
            }

            $folders[$folder][] = $name;
        }

        if ($folders === []) {
            $zip->close();

            throw new RuntimeException('ZIP içinde bölüm klasörü yok. Klasör adları bölüm numarası olmalı (1, 2, 10).');
        }

        uksort($folders, 'strnatcasecmp');
        $chapterIds = [];

        foreach ($folders as $folder => $entries) {
            $number = str_replace(',', '.', $folder);
            $chapter = Chapter::query()->firstOrCreate(
                ['series_id' => $series->id, 'number' => $number],
                [
                    'title' => $defaults['title'] ?? null,
                    'ruh_tasi_fiyat' => (int) ($defaults['ruh_tasi_fiyat'] ?? 0),
                    'vip_ucretsiz' => (bool) ($defaults['vip_ucretsiz'] ?? false),
                    'published_at' => $defaults['published_at'] ?? now(),
                    'erken_erisim_bitis' => $defaults['erken_erisim_bitis'] ?? null,
                ],
            );

            $child = new ZipArchive;
            $relative = 'chapter-zips/bulk-'.$chapter->id.'-'.uniqid().'.zip';
            $absolute = Storage::disk('local')->path($relative);
            $directory = dirname($absolute);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if ($child->open($absolute, ZipArchive::CREATE) !== true) {
                continue;
            }

            foreach ($entries as $entry) {
                $contents = $zip->getFromName($entry);

                if ($contents === false || str_ends_with($entry, '/')) {
                    continue;
                }

                $child->addFromString(basename($entry), $contents);
            }

            $child->close();
            ProcessChapterZip::dispatch($chapter->id, 'local', $relative, $userId);
            $chapterIds[] = $chapter->id;
        }

        $zip->close();

        return $chapterIds;
    }
}
