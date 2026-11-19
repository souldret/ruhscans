<?php

namespace App\Services\Ops;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemProbe
{
    /**
     * @return array{queue: int, failed: int, disk: string, laravel: string, php: string}
     */
    public function snapshot(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        return [
            'queue' => $this->safeCount(fn () => (int) DB::table('jobs')->count()),
            'failed' => $this->safeCount(fn () => (int) DB::table('failed_jobs')->count()),
            'disk' => $free === false || $total === false
                ? 'bilinmiyor'
                : $this->human($free).' boş / '.$this->human($total),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
        ];
    }

    public function database(): string
    {
        DB::select('select 1 as ok');

        return 'Veritabanı yanıt verdi.';
    }

    public function redis(): string
    {
        $pong = Redis::connection()->ping();

        return 'Redis yanıt verdi'.(is_string($pong) && $pong !== '' ? ': '.$pong : '.');
    }

    public function storage(): string
    {
        $disk = (string) config('content.image_disk', 'r2');
        Storage::disk($disk)->put('health/probe.txt', 'ok');
        Storage::disk($disk)->delete('health/probe.txt');

        return strtoupper($disk).' yazma testi geçti.';
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function check(string $target): array
    {
        try {
            $message = match ($target) {
                'database' => $this->database(),
                'redis' => $this->redis(),
                'storage' => $this->storage(),
                default => throw new \InvalidArgumentException('Bilinmeyen bağlantı.'),
            };

            return ['ok' => true, 'message' => $message];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => $this->plain($exception)];
        }
    }

    private function plain(Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message === '' || str_contains($message, 'password') || str_contains($message, 'secret')) {
            return 'Bağlantı kurulamadı.';
        }

        return mb_substr($message, 0, 180);
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (Throwable) {
            return 0;
        }
    }

    private function human(float|int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
