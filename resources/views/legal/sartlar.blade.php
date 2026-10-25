@extends('layouts.site')

@section('title', 'Kullanım Şartları — '.config('app.name'))
@section('description', config('app.name').' kullanım şartları taslağı. 18 yaş ve üzeri.')

@section('content')
    <article class="mx-auto max-w-3xl pb-16">
        <h1 class="font-display text-4xl font-semibold">Kullanım Şartları</h1>
        <p class="mt-2 text-sm text-ink-muted">Taslak. Yayımlamadan önce hukuki olarak gözden geçirilecek.</p>
        <div class="mt-8 space-y-4 text-ink-muted">
            <p>Site 18 yaş ve üzeri kişiler içindir. Kayıt olarak ve 18+ onayını vererek bu yaşta olduğunu beyan edersin. Yaş beyanın yanlışsa hesabın kapatılabilir.</p>
            <p>{{ config('app.name') }} bağımsız oyunların Türkçe yamalarını sunar. Oyunların telif ve satış hakkı geliştiricidedir. Yamalar, geliştiricinin izni olmadan ticari ürün gibi satılamaz.</p>
            <p>Hesap kişiseldir. Başkasının hesabını kullanmak, indirme sınırını aşmak için birden fazla hesap açmak ve otomatik istek yağdırmak yasaktır.</p>
            <p>Coin kodu başka bir satış kanalından alınır. Bu site kart bilgisi toplamaz. Kod devredilemez; coin bakiyenin son kullanma tarihi yoktur.</p>
            <p>İçerik haber verilmeden güncellenebilir veya kaldırılabilir. Şartlar değişirse bu sayfadaki tarih güncellenir; siteyi kullanmaya devam etmek yeni metni kabul sayılır.</p>
        </div>
    </article>
@endsection
