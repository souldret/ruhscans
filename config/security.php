<?php

return [
    'page_url_ttl' => (int) env('CONTENT_PAGE_URL_TTL', 5),

    'watermark' => (bool) env('CONTENT_WATERMARK', false),

    'read_rate' => [
        'per_minute' => (int) env('READ_RATE_PER_MINUTE', 40),
        'burst_window_seconds' => (int) env('READ_BURST_WINDOW', 10),
        'burst_max' => (int) env('READ_BURST_MAX', 12),
        'block_minutes' => (int) env('READ_BLOCK_MINUTES', 15),
    ],

    'hotlink_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HOTLINK_HOSTS', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')),
    ))),

    'trusted_proxies' => env('TRUSTED_PROXIES', '*'),

    'hsts' => (bool) env('SECURITY_HSTS', true),

    'csp' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
];
