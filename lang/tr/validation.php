<?php

return [
    'accepted' => ':attribute kabul edilmelidir.',
    'confirmed' => ':attribute doğrulaması eşleşmiyor.',
    'email' => ':attribute geçerli bir e-posta olmalıdır.',
    'file' => ':attribute bir dosya olmalıdır.',
    'image' => ':attribute geçerli bir görsel olmalıdır.',
    'mimes' => ':attribute desteklenmeyen biçim. İzin verilenler: :values.',
    'mimetypes' => ':attribute desteklenmeyen biçim. İzin verilenler: :values.',
    'uploaded' => ':attribute yüklenemedi. Dosyayı yeniden seçin.',
    'max' => [
        'file' => ':attribute dosyası çok büyük (en fazla :max KB).',
        'numeric' => ':attribute en fazla :max olabilir.',
        'string' => ':attribute en fazla :max karakter olabilir.',
    ],
    'min' => [
        'string' => ':attribute en az :min karakter olmalıdır.',
        'file' => ':attribute dosyası çok küçük (en az :min KB).',
        'numeric' => ':attribute en az :min olmalıdır.',
    ],
    'required' => ':attribute alanı zorunludur.',
    'unique' => ':attribute zaten kayıtlı.',

    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'parola',
        'code' => 'doğrulama kodu',
        'cover_path' => 'kapak',
        'cover' => 'kapak',
        'zip' => 'ZIP dosyası',
        'zip_path' => 'ZIP dosyası',
        'files.0' => 'dosya',
    ],
];
