<?php

return [
    'enabled' => (bool) env('TURNSTILE_ENABLED', false),
    'site_key' => (string) env('TURNSTILE_SITE_KEY', ''),
    'secret_key' => (string) env('TURNSTILE_SECRET_KEY', ''),
    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    'timeout' => 5,
    'token_ttl' => 300,
];
