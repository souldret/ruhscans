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

];
