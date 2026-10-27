<?php

return [

    'temporary_file_upload' => [
        'disk' => 'local',
        // 256 MB. PHP tarafında upload_max_filesize / post_max_size bundan büyük olmalı
        // (bkz. public/.user.ini ve public/.htaccess).
        'rules' => ['file', 'max:262144'],
        'directory' => 'livewire-tmp',
        // Her dosya ayrı istekle yüklenir; çok sayıda ekran görüntüsü atınca sınıra takılmasın.
        'middleware' => 'throttle:600,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a', 'm4v',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
            'webm', 'ogg', 'ogv', 'avif',
        ],
        'max_upload_time' => 60,
        'cleanup' => true,
    ],

];
