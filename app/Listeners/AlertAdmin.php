<?php

namespace App\Listeners;

use App\Services\AdminAlerter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Queue\Events\JobFailed;

class AlertAdmin
{
    public function __construct(private AdminAlerter $alerter) {}

    public function lockout(Lockout $event): void
    {
        $email = $event->request->input('email');
        $who = is_string($email) ? $email : 'bilinmeyen';

        $this->alerter->send('lockout:'.$who, 'Art arda hatalı giriş', $who.' için giriş denemesi kilitlendi.');
    }

    public function jobFailed(JobFailed $event): void
    {
        $this->alerter->send(
            'job:'.$event->job->uuid(),
            'Kuyruk işi başarısız',
            $event->job->resolveName().' kuyruğu tamamlayamadı.',
        );
    }
}
