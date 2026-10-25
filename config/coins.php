<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VIP gününün coin karşılığı
    |--------------------------------------------------------------------------
    |
    | Kullanılmamış bir VIP kodu, süresi kadar günün bu katı kadar coin'e
    | dönüşür. Süresi süren bir üyelik de kalan gün üzerinden aynı oranla
    | bakiyeye yazılır. Kesir yok: sonuç her zaman tam sayıdır.
    |
    */

    'vip_day_to_coin' => (int) env('VIP_DAY_TO_COIN', 2),

    /*
    |--------------------------------------------------------------------------
    | Eski VIP oyunlarının varsayılan fiyatı
    |--------------------------------------------------------------------------
    |
    | Geçiş, VIP olarak işaretlenmiş oyunlara bu fiyatı yazar. Sonrasında
    | yönetim panelinden toplu olarak değiştirilebilir.
    |
    */

    'default_vip_game_price' => (int) env('DEFAULT_VIP_GAME_PRICE', 100),

    // Kod yüklenince ve oyun açılınca kısa bir fiş gönder.
    'receipts' => (bool) env('COIN_RECEIPTS', false),

    /*
    | Satış iadesinde bakiye yetmezse: review (işaretle, coin alma) veya
    | negative (bakiyeyi eksiye düşür ve üyeyi incelemeye al).
    */
    'clawback' => env('COIN_CLAWBACK', 'review'),

    'anomaly' => [
        'unlock_minutes' => (int) env('COIN_UNLOCK_ANOMALY_MINUTES', 10),
        'unlock_count' => (int) env('COIN_UNLOCK_ANOMALY_COUNT', 8),
        'signup_minutes' => (int) env('COIN_SIGNUP_ANOMALY_MINUTES', 10),
        'signup_count' => (int) env('COIN_SIGNUP_ANOMALY_COUNT', 4),
        'redeem_minutes' => (int) env('COIN_REDEEM_ANOMALY_MINUTES', 10),
        'redeem_count' => (int) env('COIN_REDEEM_ANOMALY_COUNT', 5),
    ],

];
