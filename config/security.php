<?php

// Resmi Cloudflare IPv4 ve IPv6 aralıkları (https://www.cloudflare.com/ips/)
$cloudflareIps = [
    '173.245.48.0/20',
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '141.101.64.0/18',
    '108.162.192.0/18',
    '190.93.240.0/20',
    '188.114.96.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17',
    '162.158.0.0/15',
    '104.16.0.0/13',
    '104.24.0.0/14',
    '172.64.0.0/13',
    '131.0.72.0/22',
    '2400:cb00::/32',
    '2606:4700::/32',
    '2803:f800::/32',
    '2405:b500::/32',
    '2405:8100::/32',
    '2a06:98c0::/29',
    '2c0f:f248::/32',
];

$envProxies = env('TRUSTED_PROXIES');
$trustedProxies = ($envProxies === null || $envProxies === '' || $envProxies === '*')
    ? implode(',', $cloudflareIps)
    : (string) $envProxies;

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

    'trusted_proxies' => $trustedProxies,

    'cloudflare_ips' => $cloudflareIps,

    'hsts' => (bool) env('SECURITY_HSTS', true),

    'csp' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self' https://challenges.cloudflare.com; frame-src 'self' https://challenges.cloudflare.com; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
];
