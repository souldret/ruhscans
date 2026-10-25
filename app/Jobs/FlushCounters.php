<?php

namespace App\Jobs;

use App\Support\CounterBuffer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FlushCounters implements ShouldQueue
{
    use Queueable;

    public function handle(CounterBuffer $buffer): void
    {
        $buffer->flushAll();
    }
}
