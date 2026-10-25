@extends('layouts.site')

@section('title', 'Gizlilik ve KVKK — '.config('app.name'))

@section('content')
    <article class="mx-auto max-w-3xl pb-16">
        <h1 class="font-display text-4xl font-semibold">Gizlilik ve KVKK Aydınlatma Metni</h1>
        <p class="mt-2 text-sm text-ink-muted">Taslak. Yayımlamadan önce hukuki olarak gözden geçirilecek.</p>
        <div class="mt-8 space-y-4 text-ink-muted">
            <p>Veri sorumlusu: {{ config('app.name') }}. İşlenen veriler ad, e-posta, parola özeti, coin bakiyesi, indirme kaydı ve IP adresidir.</p>
            <p>Amaç: hesap açmak, üyeliği sürdürmek, indirme sınırını uygulamak ve kötüye kullanımı fark etmek. Hukuki sebep: sözleşmenin kurulması ve meşru menfaat.</p>
            <p>Veriler yurt dışındaki bir barındırıcıda tutulabilir. Üye; erişim, düzeltme, silme ve itiraz haklarını iletişim formu üzerinden kullanabilir.</p>
            <p>Parolalar düz metin olarak saklanmaz. Ödeme bilgisi bu sitede toplanmaz; coin kodu başka bir satış kanalından alınır.</p>
        </div>
    </article>
@endsection
