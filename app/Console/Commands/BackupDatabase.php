<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';

    protected $description = 'SQLite veritabanının gecelik kopyasını alır';

    public function handle(): int
    {
        $connection = (string) config('database.default');
        $path = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || ! is_string($path) || ! is_file($path)) {
            $this->warn('Yedek yalnızca sqlite dosyası için alınır.');

            return self::SUCCESS;
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $copy = $directory.'/database-'.now()->format('Ymd-His').'.sqlite';

        if (! copy($path, $copy)) {
            $this->error('Veritabanı kopyalanamadı.');

            return self::FAILURE;
        }

        collect(File::files($directory))
            ->filter(fn ($file) => str_starts_with($file->getFilename(), 'database-'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->slice(14)
            ->each(fn ($file) => File::delete($file->getPathname()));

        $this->info($copy);

        return self::SUCCESS;
    }
}
