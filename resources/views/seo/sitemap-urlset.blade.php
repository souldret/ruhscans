<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($static as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
    </url>
@endforeach
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (! empty($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
    </url>
@endforeach
</urlset>
