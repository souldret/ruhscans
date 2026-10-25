<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
@isset($links)
    @if ($links->previousPageUrl())
        <link rel="prev" href="{{ $links->previousPageUrl() }}">
    @endif
    @if ($links->nextPageUrl())
        <link rel="next" href="{{ $links->nextPageUrl() }}">
    @endif
@endisset
