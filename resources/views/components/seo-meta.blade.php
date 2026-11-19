@props(['meta' => []])
@php
    $title = $meta['title'] ?? config('seo.title_suffix');
    $full = str_contains($title, 'Ruhscans') ? $title : $title.' · '.config('seo.title_suffix');
    $description = $meta['description'] ?? config('seo.default_description');
    $canonical = $meta['canonical'] ?? url()->current();
    $image = $meta['image'] ?? asset(config('seo.og_image'));
    $robots = $meta['robots'] ?? config('seo.robots');
@endphp
<title>{{ $full }}</title>
<meta name="description" content="{{ $description }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $full }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $full }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
@if (! empty($meta['schema']))
<script type="application/ld+json">{!! json_encode($meta['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
