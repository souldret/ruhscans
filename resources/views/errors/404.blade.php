@include('errors.layout', [
    'title' => 'Sayfa yok',
    'body' => 'Bu raf boş. Aradığın seri gizlenmiş ya da hiç yayımlanmamış olabilir. Site 18 yaş ve üzeri okurlar içindir.',
    'action' => url('/'),
])
