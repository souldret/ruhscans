<?php

return [
    'site_name' => 'Ruhscans',
    'title_suffix' => 'Ruhscans',
    'default_description' => 'Ruhscans, 18 yaş ve üzeri okurlar için Türkçe manga ve manhwa kütüphanesidir.',
    'robots' => 'index, follow',
    'og_image' => 'og-adult.svg',
    'sitemap_per_page' => 1000,
    'home_ttl' => (int) env('CATALOG_HOME_TTL', 300),
    'tags_ttl' => (int) env('CATALOG_TAGS_TTL', 900),
];
