<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Services\Content\ChapterZipImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessChapterZip implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public int $chapterId,
        public string $zipDisk,
        public string $zipPath,
        public ?int $userId = null,
    ) {}

    public function handle(ChapterZipImporter $importer): void
    {
        $local = storage_path('app/chapter-imports/'.basename($this->zipPath));
        $directory = dirname($local);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($local, Storage::disk($this->zipDisk)->get($this->zipPath));

        try {
            $importer->import(Chapter::query()->findOrFail($this->chapterId), $local, $this->userId);
        } finally {
            if (is_file($local)) {
                unlink($local);
            }

            Storage::disk($this->zipDisk)->delete($this->zipPath);
        }
    }
}
