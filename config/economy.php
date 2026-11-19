<?php

return [
    'hmac_key' => env('CODE_HMAC_KEY'),
    'sonteklif_url' => env('SONTEKLIF_URL', 'https://www.sonteklif.com'),
    'redeem_attempts_per_minute' => 5,
    'redeem_failures_before_lock' => 10,
    'redeem_lock_minutes' => 60,
];
