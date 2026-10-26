<?php

return [

    /*
    |--------------------------------------------------------------------------
    | E-posta doğrulama
    |--------------------------------------------------------------------------
    |
    | Açıkken yeni üyeler e-postalarını doğrulamadan panele giremez.
    | Kapalıyken kayıt sonrası doğrudan devam edilir.
    |
    */

    'verification' => (bool) env('EMAIL_VERIFICATION', false),

    'require_login_for_free' => (bool) env('REQUIRE_LOGIN_FOR_FREE', true),

    'games_disk' => env('GAMES_DISK', 'local'),

    'signed_url_minutes' => 5,

    'download_limits' => [
        'member' => (int) env('DOWNLOAD_LIMIT_MEMBER', 5),
        'vip' => (int) env('DOWNLOAD_LIMIT_VIP', 50),
    ],

    'max_sessions' => (int) env('MAX_SESSIONS', 2),

    'bind_download_ip' => (bool) env('BIND_DOWNLOAD_IP', false),

    'anomaly_minutes' => (int) env('DOWNLOAD_ANOMALY_MINUTES', 30),

    'turnstile_site' => env('TURNSTILE_SITE_KEY'),

    'turnstile_secret' => env('TURNSTILE_SECRET_KEY'),

    'disposable_domains' => [
        'mailinator.com',
        'guerrillamail.com',
        'guerrillamailblock.com',
        '10minutemail.com',
        'tempmail.com',
        'temp-mail.org',
        'yopmail.com',
        'trashmail.com',
        'getnada.com',
        'dispostable.com',
        'sharklasers.com',
        'maildrop.cc',
        'throwawaymail.com',
    ],

];
