<?php

return [
    'health_token' => env('HEALTH_TOKEN'),
    'disk_min_bytes' => (int) env('HEALTH_DISK_MIN_BYTES', 100 * 1024 * 1024),
    'queue_stale_minutes' => (int) env('HEALTH_QUEUE_STALE_MINUTES', 15),
    'alert_email' => env('ALERT_EMAIL'),
    'sentry_dsn' => env('SENTRY_DSN'),
    'release' => env('SENTRY_RELEASE', 'dev'),
];
