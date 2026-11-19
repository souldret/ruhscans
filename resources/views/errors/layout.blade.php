<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Ruhscans</title>
    <link rel="icon" href="{{ asset('favicon-neutral.svg') }}" type="image/svg+xml">
    <style>
        :root { color-scheme: dark; }
        body { margin: 0; min-height: 100vh; background: #0B0709; color: #E8D5C4; font-family: Georgia, serif; }
        main { max-width: 36rem; margin: 0 auto; padding: 18vh 1.5rem; }
        p.kicker { letter-spacing: .22em; text-transform: uppercase; font-size: .75rem; color: #C9A227; }
        h1 { font-size: 2.4rem; font-weight: 500; margin: .6rem 0; }
        p.copy { line-height: 1.6; color: #C9B8AE; }
        a { color: #E8D5C4; }
    </style>
</head>
<body>
<main>
    <p class="kicker">Ruhscans · 18+</p>
    <h1>{{ $title }}</h1>
    <p class="copy">{{ $body }}</p>
    @isset($action)
        <p><a href="{{ $action }}">Kütüphaneye dön</a></p>
    @endisset
</main>
</body>
</html>
