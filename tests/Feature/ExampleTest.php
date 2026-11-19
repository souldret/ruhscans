<?php

use App\Http\Middleware\EnsureAdult;

it('anasayfa yaş onayı olmadan içerik döndürmez', function () {
    $this->withUnencryptedCookie(EnsureAdult::COOKIE, '')
        ->get('/')
        ->assertForbidden();
});
