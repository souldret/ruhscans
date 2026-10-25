<?php

namespace App\Services;

use App\Mail\AdminIncidentMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AdminAlerter
{
    public function send(string $key, string $title, string $detail): void
    {
        $email = config('observability.alert_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        if (! Cache::add('admin-alert:'.$key, true, now()->addHour())) {
            return;
        }

        try {
            Mail::to($email)->send(new AdminIncidentMail($title, $detail));
        } catch (Throwable) {
            Cache::forget('admin-alert:'.$key);
        }
    }
}
