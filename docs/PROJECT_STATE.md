# Ruhscans proje durumu

## Stack

- Laravel 12.73 (istenen Laravel 11 kurulamadı: 11.x güvenlik duyuruları Composer tarafından bloklanıyor)
- PHP 8.3
- PostgreSQL 16, Redis (önbellek, oturum, kuyruk)
- Filament 3, Livewire 3, Alpine.js, Tailwind (Vite)
- Laravel Breeze (Livewire + Volt)
- Spatie Permission, Spatie Activitylog
- Laravel Horizon
- Pest 3
- Cloudflare R2 (`r2` diski, S3 uyumlu)
- Intervention Image, pragmarx/google2fa-laravel
- Ödeme geçidi yok

## Mimari kurallar

- İnce controller. İş kuralları Service ve Action sınıflarında.
- Para benzeri her işlem veritabanı transaction, `lockForUpdate` ve ledger ile yapılır.
- Şema yalnızca migration ile değişir.
- Arayüz Türkçe ve mobil önceliklidir.
- Sırlar yalnızca `.env` içindedir.
- Pest testleri geçmeden faz bitmiş sayılmaz.

## Fazlar

1. Temel — bitti
2. İçerik motoru — bitti
3. Tasarım ve ön yüz — bitti
4. Okuyucu — bitti
5. Ekonomi ve kodlar — bitti
6. Güvenlik
7. SEO ve cila

## Biten

- Laravel iskeleti, Breeze kimlik akışı, Filament panel, Horizon
- PostgreSQL ve Redis varsayılanları, `docker-compose.dev.yml`
- Roller: `admin`, `ceviri_ekibi`, `uye`
- Filament yalnızca admin ve çeviri ekibine açık
- Admin için zorunlu TOTP; ilk admin seeder'ı `.env` değerlerini okur
- Kayıt, giriş, e-posta doğrulama, parola sıfırlama, profil
- Türkçe dil dosyaları, `Europe/Istanbul`
- 18+ yaş kapısı, 30 günlük çerez, yasal sayfa muafiyetleri
- Kullanıcı alanları: ruh taşı bakiyesi, VIP bitişi, gizli mod, kapak bulanıklığı, etiket karalistesi
- R2 diski
- Giriş, kayıt ve parola sıfırlama hız sınırı
- Pest: kayıt, giriş, yaş kapısı, rol erişimi, 2FA
- İçerik modelleri: seri, etiket, çeviri ekibi, bölüm, sayfa, şikayet, bölüm yüklemesi
- Soft delete, indeksler, factory ve demo seeder
- Filament CRUD; çeviri ekibi yalnızca kendi grubunun serisini yönetir
- ZIP toplu yükleme: doğal sıra, mime ve boyut doğrulama, kuyrukta WebP (kalite 82), uzun webtoon kesimi, hatalı dosya raporu
- Postgres `tsvector` + `pg_trgm` arama; filtre servisi tür, durum, etiket, seviye ve sıralama
- Yükleme formunda zorunlu yetişkin karakter onayı
- Şikayet kuyruğu ve tek tıkla gizleme
- Gece Kütüphanesi ön yüzü: sinematik ana sayfa, keşfet, seri, etiket, kütüphane, okuma tercihleri
- Kapak bulanıklığı, gizli mod, panik tuşu (düğme ve çift Esc), etiket kara listesi
- Self-host Inter ve Fraunces (`font-display: swap`), film grain, cam paneller
- Kütüphane kaydı ve puan; `panik_url` kullanıcı alanı
- Okuyucu: dikey webtoon, sayfa LTR ve RTL. Tercih `users.okuma_modu` alanında.
- Sayfa HTML'i görsel adresi taşımaz. `/sayfalar` yetki sonrası kısa ömürlü adres döner.
- Kilitli bölümde fiyat, VIP ve yer tutucu "Kod kullan" ekranı. Açılış `chapter_unlocks` kaydıyla.
- İlerleme girişli kullanıcıda debounce ile `library_entries` üzerine yazılır. Misafir localStorage. Gizli mod hiçbir şey yazmaz.
- Okuma geçmişi `/okuma-gecmisi`. Panik tuşu okuyucuda da çalışır.
- Pest: kilitli yanıtta görsel adresi yok, ilerleme, gizli mod, VIP ve açılmış bölüm
- Ledger: `wallet_transactions`, tek `WalletService` (`lockForUpdate`, idempotency). Bakiye fillable değil; model güncellemeyi yalnızca servis bayrağıyla kabul eder. PostgreSQL `ruh_tasi_bakiye >= 0` CHECK.
- Bölüm açma: `ChapterUnlocker` ruh taşı düşer, `chapter_unlocks` tekil. VIP `vip_ucretsiz` ve erken erişimi açar. `erken_erisim_bitis` geçince bölüm herkese ücretsiz.
- VIP: aktifse `vip_bitis` üzerine gün eklenir, değilse şimdiden başlar. Altın `.badge-vip`.
- Kodlar: `RUH-XXXX-XXXX-XXXX-XXXX`, Crockford base32, HMAC-SHA256. Düz kod DB'de yok; üretim anında tek seferlik CSV. Filament parti, iptal, hash arama. Kullanımda dakika sınırı, 10 hatada 1 saat kilit, maskeli log.
- Cüzdan: bakiye, VIP, işlem geçmişi, kod formu, Sonteklif linki (`site_settings`).
- Pest: eşzamanlı kod, süresi dolmuş, iptal, hız sınırı, VIP uzatma, yetersiz bakiye, çift açılış, idempotency

## Kararlar

- Laravel 12 korunur. İçerik şeması migration ile gelir.
- Testler SQLite bellek üzerindedir. `search_vector` ve `pg_trgm` yalnızca PostgreSQL bağlantısında oluşturulur. SQLite araması başlık ve alternatif adların katlanmış ön ekiyle çalışır; yazım toleransı üretimde `similarity()` ile sağlanır.
- Sayfa görselleri orijinal formatta saklanmaz. Kuyruk WebP üretir ve kaynak ZIP'i siler.
- Çeviri ekibi kullanıcısı `translator_group_id` ile bir gruba bağlanır. Policy ve Filament sorgusu bu gruba kilitlenir.
- Bölüm şikayetinde bölüm yumuşak silinir, seri gizlenir. Seri şikayetinde yalnızca gizli bayrağı açılır.
- Uzun görsel, yapılandırılmış azami yükseklikte ve renk değişiminin en az olduğu satırdan kesilir.
- Okuma ilerlemesi ayrı tablo değil, `library_entries.last_page_order` ve `completed` alanlarıdır. Misafir ilerlemesi yalnızca tarayıcıda kalır.
- Kilit nedenleri: yayımlanmamış, erken erişim, fiyat. VIP, `vip_ucretsiz` veya erken erişimde geçer. Ücretli bölüm `ChapterUnlocker` ile açılır: kullanıcı satırı kilitlenir, tek `chapter_unlocks` kaydı ve `unlock:{user}:{chapter}` borç anahtarı yazılır.
- Erken erişim süresi dolunca fiyat da kalkar; bölüm herkese açıktır. Erken erişim sürerken unlock kaydı kilidi açmaz.
- Görsel adresi `Storage::temporaryUrl` ile üretilir (`CONTENT_PAGE_URL_TTL`, varsayılan 15 dakika). İmzalama yoksa düz `url()` düşer. Presigned sertleştirme faz 6'da yapılacak.
- Kodlar düz saklanmaz. Normalize (boşluk, tire, küçük harf, 0/O 1/I L/I) sonrası HMAC-SHA256 (`CODE_HMAC_KEY`). Son 4 hane arama içindir. Hatalı, süresi dolmuş ve iptal kod aynı "Kod kullanılamadı." der.
- Ödeme geçidi yok. Kodlar Sonteklif'te satılır. Cüzdan linki admin `site_settings.sonteklif_url`, yoksa `SONTEKLIF_URL`.

## Ortam değişkenleri

`APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_DEFAULT_REGION`, `R2_BUCKET`, `R2_URL`, `R2_ENDPOINT`, `R2_USE_PATH_STYLE_ENDPOINT`, `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `CONTENT_IMAGE_DISK`, `CONTENT_WEBP_QUALITY`, `CONTENT_MAX_ZIP_KB`, `CONTENT_MAX_IMAGE_KB`, `CONTENT_MAX_PAGE_HEIGHT`, `CONTENT_SEARCH_SIMILARITY`, `CONTENT_PAGE_URL_TTL`, `CODE_HMAC_KEY`, `SONTEKLIF_URL`.

## Bilinen eksikler

- `CODE_HMAC_KEY` boşsa kod üretimi ve kullanımı reddedilir. Anahtar yalnızca `.env` içindedir.
- Yasal metinler taslak; hukuk incelemesi ve şikayet formu yok.
- Windows'ta Horizon `pcntl` / `posix` olmadan çalışmaz. Üretim Linux olmalı.
- R2 anahtarları boşken dosya yüklenemez.
- İlk admin, `.env` doldurulmadan seed edilmez ve 2FA onaylamadan panele giremez.
- Giriş, kayıt ve profil formları hâlâ Breeze düzeninde; gece temasına alınmadı.
- Şikayet oluşturma henüz admin formunda. Ziyaretçi şikayet sayfası yok.
- PostgreSQL arama indeksi bu ortamda çalıştırılmadı; migration Postgres sürücüsünde `pg_trgm` ve GIN indeksini kurar.
- R2 boşken sayfa yüklemesi `CONTENT_IMAGE_DISK=local` ile yerel diske düşürülebilir.

## Sonraki adım

Faz 6: kısa ömürlü presigned URL sertleştirmesi ve güvenlik.
