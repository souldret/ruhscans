@include('errors.layout', [
    'title' => 'Giriş yok',
    'body' => 'Bu sayfa sana açık değil. Ruhscans 18 yaş ve üzeri içerik barındırır; yetkin yoksa geri dön.',
    'action' => url('/'),
])
