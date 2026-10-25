# Deneme adımları

1. `php artisan renpy:splash` çalıştır.
2. `storage/app/renpy/zz_splash.rpy` dosyasını bu klasördeki `game/` içine kopyala.
3. `game/script.rpy` içindeki `label start` satırının üstüne `call rt_gate` yaz.
4. Ren'Py 8.2 veya daha yeni SDK ile projeyi aç. Giriş ekranı çıkmalı, oyun çökmemeli.
5. Aynı dosyayı, içinde `label splashscreen` olan bir oyuna koy. Oyun kendi açılışıyla açılmalı. Kapı için splashscreen sonuna `jump rt_gate` eklenir.
6. Ağ kapalıyken, daha önce girilmiş ve 7 günü dolmamış token ile oyun açılmalı. Süre dolmuşsa Türkçe hata çıkmalı.
