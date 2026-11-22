<?php

return [
    'image_disk' => env('CONTENT_IMAGE_DISK', 'r2'),
    'webp_quality' => (int) env('CONTENT_WEBP_QUALITY', 82),
    'max_zip_kilobytes' => (int) env('CONTENT_MAX_ZIP_KB', 204800),
    'max_image_kilobytes' => (int) env('CONTENT_MAX_IMAGE_KB', 20480),
    'max_page_height' => (int) env('CONTENT_MAX_PAGE_HEIGHT', 16000),
    'max_page_width' => (int) env('CONTENT_MAX_PAGE_WIDTH', 1600),
    'search_similarity' => (float) env('CONTENT_SEARCH_SIMILARITY', 0.2),
    'page_url_ttl' => (int) env('CONTENT_PAGE_URL_TTL', 5),

    // Kapak: jpg/png/webp, en fazla 10 MB, kuyrukta WebP varyantları üretilir.
    'max_cover_kilobytes' => (int) env('CONTENT_MAX_COVER_KB', 10240),
    'cover_width' => 600,
    'cover_height' => 900,
    'cover_small_width' => 300,
    'cover_small_height' => 450,
    'cover_placeholder_width' => 20,
    'cover_placeholder_height' => 30,
    'cover_min_width' => (int) env('CONTENT_COVER_MIN_WIDTH', 300),
    'cover_min_height' => (int) env('CONTENT_COVER_MIN_HEIGHT', 450),
    'cover_max_width' => (int) env('CONTENT_COVER_MAX_WIDTH', 6000),
    'cover_max_height' => (int) env('CONTENT_COVER_MAX_HEIGHT', 9000),

    // Cloudflare 100 MB istek gövdesi sınırı. Üstü tarayıcıdan doğrudan R2'ye yüklenir.
    'direct_upload_threshold_kb' => (int) env('CONTENT_DIRECT_UPLOAD_THRESHOLD_KB', 100000),
    'direct_upload_ttl_minutes' => (int) env('CONTENT_DIRECT_UPLOAD_TTL', 30),

    // ZIP güvenliği: zip-slip, zip bombası, dosya sayısı ve tehlikeli uzantılar.
    'max_zip_files' => (int) env('CONTENT_MAX_ZIP_FILES', 500),
    'max_zip_uncompressed_kilobytes' => (int) env('CONTENT_MAX_ZIP_UNCOMPRESSED_KB', 512000),
    'max_zip_compression_ratio' => (int) env('CONTENT_MAX_ZIP_RATIO', 200),
    'zip_allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],

    // CDN üzerinden kapak servisi. R2_URL https içermiyorsa eklenir.
    'cdn_url' => env('R2_URL'),
    'cover_cache_ttl' => (int) env('CONTENT_COVER_CACHE_TTL', 31536000),
];
