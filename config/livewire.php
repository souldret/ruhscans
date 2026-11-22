<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire, dosyayı kalıcı diske yazmadan önce tüm yüklemeleri bu geçici
    | kapıdan geçirir. Varsayılan sınır 12 MB olduğu için kapak ve ZIP formları
    | kendi maxSize kurallarından önce burada reddediliyordu. Cloudflare istek
    | gövdesi 100 MB ile sınırlı olduğundan geçici sınır 100 MB'ın biraz
    | altında tutulur; daha büyük ZIP'ler tarayıcıdan doğrudan R2'ye gider.
    |
    */

    'temporary_file_upload' => [
        'disk' => env('FILESYSTEM_DISK', 'local'),
        'rules' => ['required', 'file', 'max:'.(int) env('CONTENT_DIRECT_UPLOAD_THRESHOLD_KB', 100000)],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 10,
        'cleanup' => true,
    ],

];
