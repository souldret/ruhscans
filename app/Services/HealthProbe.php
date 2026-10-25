<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthProbe
{
    /**
     * @return array{status: string, checks: array<string, string>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->database() ? 'ok' : 'down',
            'storage' => $this->storage() ? 'ok' : 'down',
            'queue' => $this->queue() ? 'ok' : 'down',
            'disk' => $this->disk() ? 'ok' : 'down',
        ];

        return [
            'status' => in_array('down', $checks, true) ? 'down' : 'ok',
            'checks' => $checks,
        ];
    }

    private function database(): bool
    {
        try {
            DB::select('select 1 as ok');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function storage(): bool
    {
        try {
            $disk = Storage::disk((string) config('membership.games_disk', 'local'));
            $disk->put('health/ping.txt', 'ok');
            $disk->delete('health/ping.txt');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function queue(): bool
    {
        if (config('queue.default') === 'sync') {
            return true;
        }

        try {
            if (! Schema::hasTable('jobs')) {
                return false;
            }

            $cutoff = now()->subMinutes((int) config('observability.queue_stale_minutes', 15))->getTimestamp();
            $stale = DB::table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<', $cutoff)
                ->count();

            if ($stale > 0) {
                app(AdminAlerter::class)->send('queue-stalled', 'Kuyruk durdu', $stale.' iş 15 dakikadır bekliyor.');

                return false;
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function disk(): bool
    {
        $free = disk_free_space(storage_path());
        $minimum = (int) config('observability.disk_min_bytes');
        $healthy = is_float($free) && $free > $minimum;

        if (! $healthy) {
            app(AdminAlerter::class)->send('disk-full', 'Disk doluyor', 'Depolama alanı eşiğin altına indi.');
        }

        return $healthy;
    }
}
