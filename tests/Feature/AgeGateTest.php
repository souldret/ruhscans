<?php

use App\Http\Middleware\EnsureAdult;

test('ziyaretçi içerik sayfasını yaş kapısı olmadan göremez', function () {
    $this->withUnencryptedCookie(EnsureAdult::COOKIE, '')
        ->get('/')
        ->assertForbidden()
        ->assertSee('18 yaşından büyüğüm');
});

test('yasal sayfalar yaş kapısından muaftır', function (string $path) {
    $this->get($path)->assertOk();
})->with([
    '/kullanim-sartlari',
    '/gizlilik',
    '/dmca',
    '/18-uyari',
    '/sikayet',
    '/robots.txt',
    '/sitemap.xml',
]);

test('onay çerezi içerik sayfasını açar ve otuz gün saklanır', function () {
    $this->post('/18-onay', ['intended' => url('/')])
        ->assertRedirect('/')
        ->assertCookie(EnsureAdult::COOKIE, '1');

    $this->withCookie(EnsureAdult::COOKIE, '1')
        ->get('/')
        ->assertOk()
        ->assertSee('Ruhscans');
});
