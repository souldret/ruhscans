<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sayfa bulunamadı — {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #09090b; color: #f4f4f5; font-family: sans-serif; }
        a { color: #fb7185; }
    </style>
</head>
<body>
    <div>
        <h1>Sayfa bulunamadı</h1>
        <p>Aradığın sayfa yok ya da taşınmış olabilir.</p>
        <p><a href="{{ url('/') }}">Ana sayfaya dön</a></p>
    </div>
</body>
</html>
