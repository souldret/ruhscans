<?php

use App\Jobs\FlushCounters;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Önbellekte biriken görüntülenme sayaçlarını düzenli olarak veritabanına yaz.
Schedule::job(new FlushCounters)->everyFiveMinutes()->withoutOverlapping();

// Defter toplamı bakiyeden ayrılırsa yöneticiye haber ver.
Schedule::command('coins:audit')->daily()->withoutOverlapping();
