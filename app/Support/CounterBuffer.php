<?php

namespace App\Support;

use App\Models\Game;
use Illuminate\Support\Facades\Cache;

class CounterBuffer
{
    public function add(string $column, int $gameId): void
    {
        $key = "counter:{$column}:{$gameId}";
        $count = (int) Cache::increment($key);

        if ($count === 1) {
            Cache::put($key, 1, now()->addDay());
        }

        if ($count % 25 === 0) {
            $this->flush($column, $gameId);
        }
    }

    public function flush(string $column, int $gameId): void
    {
        $key = "counter:{$column}:{$gameId}";
        $count = (int) Cache::pull($key);

        if ($count < 1) {
            return;
        }

        Game::query()->whereKey($gameId)->toBase()->increment($column, $count);
    }

    public function flushAll(): int
    {
        $flushed = 0;

        foreach (['view_count', 'download_count'] as $column) {
            foreach (Game::query()->pluck('id') as $id) {
                $before = (int) Cache::get("counter:{$column}:{$id}", 0);

                if ($before < 1) {
                    continue;
                }

                $this->flush($column, (int) $id);
                $flushed++;
            }
        }

        return $flushed;
    }
}
