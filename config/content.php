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
];
