<?php

return [

    /*
    | Aktif Ed25519 anahtar kimliği. İmza bu anahtarla atılır.
    | Açık anahtarlar LAUNCHER_PUBLIC_KEYS içinde "kid:base64" olarak,
    | virgülle ayrılmış tutulur. Döndürme: yeni çifti ekle, kid'i değiştir,
    | eski açık anahtarı bir süre listede bırak.
    */
    'key_id' => env('LAUNCHER_KEY_ID', 'k1'),

    'private_key' => env('LAUNCHER_PRIVATE_KEY'),

    'public_keys' => env('LAUNCHER_PUBLIC_KEYS', ''),

    'payload_ttl_seconds' => (int) env('LAUNCHER_PAYLOAD_TTL', 120),

    /*
    | Hesap + oyun başına, son N günde görülen aktif cihaz.
    */
    'device_limit' => (int) env('LAUNCHER_DEVICE_LIMIT', 3),

    'device_window_days' => (int) env('LAUNCHER_DEVICE_WINDOW_DAYS', 30),

    /*
    | Aynı kullanıcı ve oyun için bu süre içinde gelen yeni cihaz,
    | kotayı doldurmadan önce kaldırılabilir olarak işaretlenir.
    */
    'reinstall_grace_minutes' => (int) env('LAUNCHER_REINSTALL_GRACE_MINUTES', 30),

    /*
    | /api/v1/login kaba kuvvet. Oyun içi istemci Turnstile kullanamaz.
    */
    'login' => [
        'ip_per_minute' => (int) env('LAUNCHER_LOGIN_IP_PER_MINUTE', 10),
        'account_per_minute' => (int) env('LAUNCHER_LOGIN_ACCOUNT_PER_MINUTE', 5),
        'delay_step_ms' => (int) env('LAUNCHER_LOGIN_DELAY_STEP_MS', 400),
        'max_delay_ms' => (int) env('LAUNCHER_LOGIN_MAX_DELAY_MS', 4000),
        'lock_after' => (int) env('LAUNCHER_LOGIN_LOCK_AFTER', 8),
        'lock_seconds' => (int) env('LAUNCHER_LOGIN_LOCK_SECONDS', 900),
        'alert_after' => (int) env('LAUNCHER_LOGIN_ALERT_AFTER', 5),
    ],

];
