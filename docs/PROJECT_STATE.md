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
- Intervention Image, Cloudflare Turnstile
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
6. Güvenlik — bitti
7. SEO ve cila — bitti

Tüm fazlar tamam. Admin paneli denetimi (2026-11-22) sonrası içerik, ekonomi, üye ve sistem ekranları dolduruldu; 2FA kaldırıldı, Turnstile ve panel giriş sertleştirmesi eklendi.

## Biten

- Laravel iskeleti, Breeze kimlik akışı, Filament panel, Horizon
- PostgreSQL ve Redis varsayılanları, `docker-compose.dev.yml`
- Roller: `admin`, `ceviri_ekibi`, `uye`
- Filament yalnızca admin ve çeviri ekibine açık; 2FA yok, engelli kullanıcı giremez
- İki faktör tamamen kaldırıldı: servis, challenge controller/route, Filament güvenlik sayfası, model alanları ve `google2fa` bağımlılığı yok. Migration `users.two_factor_*` sütunlarını düşürür, `down` geri ekler.
- Kayıt, giriş, e-posta doğrulama, parola sıfırlama, profil
- Türkçe dil dosyaları, `Europe/Istanbul`
- 18+ yaş kapısı, 30 günlük çerez, yasal sayfa muafiyetleri
- Kullanıcı alanları: ruh taşı bakiyesi, VIP bitişi, gizli mod, kapak bulanıklığı, etiket karalistesi
- R2 diski
- Giriş, kayıt ve parola sıfırlama hız sınırı
- Cloudflare Turnstile: `siteverify` 5 sn timeout, `CF-Connecting-IP` → `remoteip`, hata/zaman aşımında istek reddi, token tek kullanımlık. Giriş, kayıt, şifre sıfırlama isteği, Filament girişi, kod kullanma, şikayet ve DMCA formlarında.
- Admin giriş güvenliği: IP+e-posta başına dakikada 5 deneme, 10 ardışık hatada 15 dk kilit, başarılı/başarısız giriş `admin-login` aktivite günlüğü, girişte session regenerate.
- Pest: kayıt, giriş, yaş kapısı, rol erişimi, Turnstile (`Http::fake`)
- İçerik modelleri: seri, etiket, bölüm, sayfa, şikayet, bölüm yüklemesi
- Soft delete, indeksler, factory ve demo seeder
- Filament CRUD; çeviri ekibi tüm seri/bölüm/sayfa/etiket/şikayet içeriğini yönetir
- Site header ve mobil menüde yetkiliye "Admin Paneli"; Filament üst çubuk ve kullanıcı menüsünde "Siteye Dön"
- ZIP toplu yükleme: doğal sıra, mime ve boyut doğrulama, kuyrukta WebP (kalite 82), uzun webtoon kesimi, hatalı dosya raporu
- Postgres `tsvector` + `pg_trgm` arama; filtre servisi tür, durum, etiket, seviye ve sıralama
- Yükleme formunda zorunlu yetişkin karakter onayı
- Şikayet kuyruğu ve tek tıkla gizleme; çeviri ekibi de kuyruğa erişir
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
- Sayfa adresi yetkiden sonra en fazla 5 dakika. R2/S3 `temporaryUrl`; diğer disklerde imzalı `reader.media` ve ikinci yetki kontrolü.
- Hotlink: boş Referer/Origin serbest; yabancı host 403. Okuma hızı kullanıcı veya IP başına dakika ve patlama penceresi; aşımda 15 dk önbellek bloğu. `read_blocked_until` fillable değil.
- Filigran Site Ayarları sayfasından (`watermark_enabled`) veya `CONTENT_WATERMARK`. Metin JSON ve görsel alt metninde.
- Güvenlik başlıkları: CSP (`challenges.cloudflare.com` Turnstile için açık), HSTS, X-Frame-Options DENY, nosniff, Referrer-Policy. Şifre en az 12, büyük-küçük, rakam, sembol.
- Cloudflare `CF-Connecting-IP` gerçek istemci IP’si; `trustProxies` ve hız sınırı bu adrese bağlı.
- `/sikayet` ve `/dmca` formları, honeypot `website`, `throttle:5,10`, Turnstile. Admin işlem günlüğü salt okunur.
- Pest: yetkisiz presigned yok, süre dolunca 403, hız sınırı, başlıklar, yabancı referer, honeypot
- Sitemap, robots, canonical, nötr OG (`og-adult.svg`), Book/ComicSeries ve ComicIssue. Bölüm sayfası `noindex, follow`.
- Katalog önbelleği kimlik listesi tutar; kara liste bellekten uygulanır. TTL: ana sayfa 300 sn, etiket 900 sn.
- Geniş sayfa `CONTENT_MAX_PAGE_WIDTH` (1600) değerine ölçeklenir. Katalog indeksleri: üye tarihi, gizli seri, yayın tarihi, okuma, kod kullanımı.
- Yönetici özeti (günlük okuma, yeni üye, kod, bekleyen şikayet), site ayarları (panik, Sonteklif, duyuru) ve bakım modu.
- 404, 403, 500, 503 gece temasında ve aynı 18+ uyarıyla.
- `php artisan optimize` config dosyalarında çapraz `config()` çağrısı olmadan çalışır.
- Admin paneli: seri/bölüm/sayfa, toplu ZIP, tek seferlik kod CSV, üye, şikayet, site ayarları, sistem durumu.
- Panel erişimi engelli olmayan `admin` ve `ceviri_ekibi` rollerine açık; 2FA kapısı yok.

## Admin denetimi (2026-11-22)

Canlı panel boş görünüyordu. Kodda seri, bölüm, tek ZIP, kod partisi, cüzdan servisi, şikayet ve site ayarları vardı. Ayrı üye, rol, kod, cüzdan kaynakları, sistem durumu, toplu ZIP, sayfa sıralama ve kalıcı tek seferlik CSV yoktu. Eski 2FA kapısı kaldırıldı; engelli olmayan admin/çeviri ekibi doğrudan panele girer.

| Özellik | Kod | Filament | Durum |
| --- | --- | --- | --- |
| Seri / bölüm / sayfa | vardı | vardı, form eksik | tam form, kapak, filtre, toplu işlem |
| ZIP yükleme | tek ZIP vardı | form zayıftı | kuyruk, rozet, yeniden dene, toplu klasör ZIP |
| Kod üretimi | servis vardı | parti listesi | Kod Üret, tek seferlik CSV, 10.000+ kuyruk |
| Cüzdan | servis vardı | yok | salt okunur işlem listesi, üye detayı |
| Kullanıcı yönetimi | model vardı | yok | üye, rol, gerekçeli taş/VIP, engel |
| Şikayet kuyruğu | vardı | kısmi | içeriği aç, gizle, incelendi |
| Ayarlar | tablo vardı | dağınık sayfalar | tek Site Ayarları, cache |

Yetki: `Gate::before` admin rolüne her yetenekte true döner. `ceviri_ekibi` tüm içeriği (seri/bölüm/sayfa/etiket/şikayet) yönetir; şikayet kuyruğu `AdminAccess::staff()` ile açıktır. Ekonomi, kullanıcı ve sistem menüleri `AdminAccess::admin()` ile kapalıdır.

## Güvenlik ve panel değişiklikleri (2026-11-22)

- 2FA tamamen kaldırıldı; `google2fa` composer'dan çıktı. `users.two_factor_*` sütunları migration ile düşürülüyor, `down` geri ekliyor.
- Turnstile doğrulaması zorunlu: boş/tekrar kullanılan token, servis hatası ve zaman aşımı isteği reddeder. CSP `challenges.cloudflare.com` içerir.
- Panel girişi: dakikada 5 deneme, 10 hatada 15 dk kilit, activity log, session regenerate, en az 12 karakter şifre.
- Çeviri grupları kaldırıldı; `ceviri_ekibi` içerik bazlı sınır olmadan tüm içeriği yönetir. Grup tablosu ve foreign key'leri güvenli migration ile düşürülür, `down` geri kurar.
- Site ↔ panel geçiş butonları header, mobil menü, Filament topbar ve kullanıcı menüsünde.

Canlıda çalıştırılacaklar:

```bash
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

## Kapak ve ZIP yükleme düzeltmeleri (2026-11-22)

### Kök neden

Admin panelinde kapak yüklemesinin validation hatası vermesinin nedeni Livewire geçici yükleme kapısıydı. Livewire 3 varsayılan `temporary_file_upload.rules` değeri `max:12288` (12 MB) olduğu için dosya, Filament `FileUpload::maxSize()` kuralına hiç ulaşmadan reddediliyordu. Yerelde 13 MB dosya ile `FileUploadController::validateAndStore` çağrılarak bu durum yeniden üretildi ve hata mesajı doğrulandı.

### Kapak akışı

- `config/livewire.php` eklendi; geçici yükleme sınırı `CONTENT_DIRECT_UPLOAD_THRESHOLD_KB` (varsayılan 100000 KB) değerine bağlandı.
- Kapak alanı yalnızca JPG/PNG/WebP kabul eder, 10 MB sınırı ve gerçek MIME kontrolü (`finfo`) uygular.
- Kapak piksel sınırları eklendi: varsayılan en az 300×450, en fazla 6000×9000.
- `ProcessSeriesCover` kuyruk job'ı Intervention ile `cover.webp` (600×900), `cover-small.webp` (300×450) ve bulanık `cover-placeholder.webp` üretir; `ContentType: image/webp` ve uzun ömürlü `Cache-Control` yazar.
- Eski kapak ve türevleri değiştirme sırasında silinir.
- `CoverUrl` ve `Series` erişimcileri kapakları `R2_URL` CDN adresiyle üretir. `R2_URL` `https://` içermiyorsa otomatik eklenir.
- R2 diski `visibility => private` kullanır; ACL isteği gönderilmez.
- Türkçe doğrulama mesajları eklendi: yüklenemedi, çok büyük, desteklenmeyen biçim, piksel boyutu.
- Ön yüz kapak kartı placeholder → small → full katmanlarıyla yüklenir.

### ZIP güvenliği ve büyük dosya akışı

- `ChapterZipImporter`: zip-slip, zip bombası (sıkıştırma oranı ve toplam açılmamış boyut), dosya sayısı, uzantı ve MIME allowlist kontrolleri uygular. SVG/PHP gibi dosyalar reddedilir.
- 100 MB altındaki ZIP'ler mevcut Livewire akışıyla çalışmaya devam eder.
- 100 MB üstü ZIP'ler için tarayıcıdan doğrudan R2'ye yükleme eklendi:
  - tek PUT için presigned URL,
  - 16 MB parçalarla S3 multipart,
  - tek kullanımlık yükleme token'ı,
  - sunucuda yalnızca `ProcessChapterZip` job'ını tetikleyen endpoint.
- Bu akış şu an bölüm "ZIP yükle / yeniden dene" satır aksiyonunda kullanılabilir. Create/edit chapter formları yerel ZIP akışını kullanmaya devam eder.
- Vite girdisi ve Filament script hook'u ile `direct-zip-upload.js` panele yüklenir.

### Testler

- `CoverUploadTest`: geçerli kapak, 10 MB üstü, düşük piksel, yanlış MIME/sahte JPG, R2 fake disk ile WebP türevleri ve eski dosya silme, CDN URL.
- `ChapterZipSecurityTest`: zip-slip, tehlikeli uzantılar, dosya sayısı limiti.
- `DirectZipUploadTest`: eşik kontrolü, tek kullanımlık token, yetki, job dispatch, rota koruması.
- Son tam suite: **110 passed (500 assertions)**.

### Canlıda yapılacaklar

Web PHP (`lsphp83`) için `upload_max_filesize` ve `post_max_size` en az 100 MB olmalı; 100 MB üstü dosyalar zaten tarayıcıdan R2'ye gider. `.env` içinde `CONTENT_MAX_COVER_KB=10240`, `CONTENT_DIRECT_UPLOAD_THRESHOLD_KB=100000`, `CONTENT_DIRECT_UPLOAD_TTL=30`, `CONTENT_COVER_MIN_WIDTH=300`, `CONTENT_COVER_MIN_HEIGHT=450`, `CONTENT_COVER_MAX_WIDTH=6000`, `CONTENT_COVER_MAX_HEIGHT=9000`, `CONTENT_MAX_ZIP_FILES=500`, `CONTENT_MAX_ZIP_UNCOMPRESSED_KB=512000`, `CONTENT_MAX_ZIP_RATIO=200`, `CONTENT_COVER_CACHE_TTL=31536000` tanımlanır. `R2_URL` https ile başlamalıdır.

R2 bucket CORS (tarayıcıdan PUT için):

```json
[
  {
    "AllowedOrigins": ["https://ruhscans.com"],
    "AllowedMethods": ["PUT", "GET", "HEAD"],
    "AllowedHeaders": ["Content-Type", "x-amz-*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

Canlı komutlar:

```bash
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
```

Test adımları: admin panelinde 8 MB geçerli JPG kapak yükle, 11 MB dosyanın "çok büyük" mesajını verdiğini gör, sahte JPG'nin "Desteklenmeyen biçim" mesajını verdiğini gör, 100 MB altı ZIP'i normal akışla yükle, 100 MB üstü ZIP'i "Büyük ZIP seç" ile yükleyip kuyrukta `ProcessChapterZip` oluşumunu kontrol et.

## Mobil öncelikli Gece Kütüphanesi yenilemesi (2026-11-23)

- Tüm ön yüz 360–412 px mobil öncelikli olarak yenilendi; tablet (768 px) ve masaüstü (1280 px) genişlemeleri doğrulandı.
- Gece Kütüphanesi kimliği korundu (`#0B0709` zemin, `#5A0F2E` şarap, `#FF2E88` magenta, `#E8C26A` VIP altın, Fraunces + Inter), `100dvh`, `env(safe-area-inset-*)`, 44 px+ dokunma hedefleri, 16 px+ gövde/form metni, sıfır yatay taşma ve `prefers-reduced-motion` desteği uygulandı.
- Navigasyon: mobilde 4 sekmeli alt gezinme çubuğu (Keşfet, Kütüphane, Ara, Profil), yapışkan üst çubukta blur geçişi ve üst sağda yanlışlıkla basılmayan erişilebilir panik tuşu; masaüstünde üst menü.
- Ana sayfa: mobilde tek kart kaydırılabilir vitrin + nokta göstergeleri, "Kaldığın yerden devam et", 2 sütun "Yeni bölümler", yatay snap "Bu gece trend", tür çipleri ve VIP bandı.
- Keşfet: yapışkan arama çubuğu, alttan açılan filtre sheet'i (tür/durum/seviye), sıralama çipleri, skeleton yükleme durumu ve sonsuz kaydırma/"Daha fazla göster".
- Seri ve okuyucu: mobilde alt gezinme üstünde sabit "Okumaya başla/Devam et" butonu, masaüstünde hero CTA, net VIP/taş/erken rozetleri, tam ekran odak modu, ekrana dokunarak araç çubuğu aç/kapat, sayfa modunda sol/sağ dokunma bölgeleri, yanıp sönmeyi önleyen oran korumalı sayfa kapları ve sonraki bölüm önyükleme.
- Giriş, kayıt, şifre sıfırlama, profil (`/profile`), tercihler (`/okuma-tercihleri`), cüzdan (`/cuzdan`), kütüphane (`/kutuphane`), yaş kapısı, yasal sayfalar ve hata sayfaları tamamen Gece Kütüphanesi temasına alındı.
- PWA kabuğu: `public/manifest.json`, `public/icons/*`, `public/offline.html` ve yalnızca kabuk/derlenmiş varlıkları önbelleğe alan `public/sw.js` (kapaklar, sayfa görselleri ve +18 içerik asla çevrimdışı önbelleğe alınmaz).
- Görsel doğrulama ve testler: Playwright ile 4 boyutta (360×800, 390×844, 768×1024, 1280×800) 56 ekran görüntüsü `docs/screenshots/` altına alındı (0 taşma, 0 küçük dokunma hedefi, 0 input font sorunu); `tests/e2e/smoke.spec.cjs` ve `tests/Feature/Ui/MobileFirstUiTest.php` eklendi.

## Profil merkezi yenilemesi (2026-11-24)

- `/profile` sayfası mobil öncelikli sekmeli/kartlı düzenle baştan kuruldu (`ProfileController` + Livewire 3 bileşenleri: `SummaryCard`, `PrivacySettings`, `WalletSummary`, `SessionsManager`, `ReadingHistoryManager` + yükseltilmiş Volt hesap formları).
- Özet kartı: kare kırpma ve otomatik 256×256 WebP dönüşümlü avatar yükleme (`AvatarService`, R2 özel yol `avatars/{user_id}/{uuid}.webp`, gerçek `finfo` MIME kontrolü, varsayılan gradient avatar), üyelik tarihi, ruh taşı bakiyesi, VIP kalan gün, hızlı "Kod kullan" ve JSON veri indirme butonları.
- Hesap: Türkçe doğrulama mesajları; e-posta değiştirmede Turnstile + rate limit + **e-posta enumeration koruması** (başkasına ait adreste genel mesaj); şifre değiştirmede 12+ güçlü şifre kuralı + diğer cihaz oturumlarını otomatik kapatma + şifresiz aktivite günlüğü; hesap silmede şifre onayı ve açık silme/anonimleştirme bildirimi (`AccountDeletionService`: kişisel veriler/avatar/oturumlar/kütüphane silinir, mali denetim bütünlüğü için `wallet_transactions.user_id` ve `redeem_codes.kullanan_user_id` `null` yapılarak anonimleştirilir).
- Gizlilik ve +18: kapak blur, gizli mod ve nötr sekme başlığı (`users.notr_sekme_baslik`) anında kaydolur; panik hedef URL'si `SafePanicUrl` kuralıyla yalnızca güvenli `http://`/`https://` adreslerini kabul eder (javascript/localhost/özel IP reddedilir); etiket kara listesi arama + çip arayüzüyle anında güncellenir.
- Cüzdan özeti (son 10 hareket + açılan bölümler), aktif cihaz/oturum takibi (`user_sessions` tablosu + `TrackUserSession` middleware — Redis/file/DB tüm oturum sürücüleriyle uyumlu + tekil/"diğer cihazlardan çıkış yap"), okuma geçmişi tek tek silme/toplu temizleme ve rate-limited JSON veri dışa aktarma (`/profile/veri-indir`).
- Güvenlik ve testler: her uç nokta ve Livewire aksiyonunda yetki ve IDOR koruması, rate limit ve hassas alan hariç aktivite günlüğü; `tests/Feature/Profile/ProfileHubTest.php` eklendi.

## Kararlar

- Laravel 12 korunur. İçerik şeması migration ile gelir.
- Testler SQLite bellek üzerindedir. `search_vector` ve `pg_trgm` yalnızca PostgreSQL bağlantısında oluşturulur. SQLite araması başlık ve alternatif adların katlanmış ön ekiyle çalışır; yazım toleransı üretimde `similarity()` ile sağlanır.
- Sayfa görselleri orijinal formatta saklanmaz. Kuyruk WebP üretir ve kaynak ZIP'i siler.
- Çeviri grupları kaldırıldı. `ceviri_ekibi` tüm seri/bölüm/sayfa/etiket/şikayeti yönetir; grup tablosu ve foreign key'leri güvenli migration ile düşürülür (`down` geri kurar).
- Bölüm şikayetinde bölüm yumuşak silinir, seri gizlenir. Seri şikayetinde yalnızca gizli bayrağı açılır.
- Uzun görsel, yapılandırılmış azami yükseklikte ve renk değişiminin en az olduğu satırdan kesilir.
- Okuma ilerlemesi ayrı tablo değil, `library_entries.last_page_order` ve `completed` alanlarıdır. Misafir ilerlemesi yalnızca tarayıcıda kalır.
- Kilit nedenleri: yayımlanmamış, erken erişim, fiyat. VIP, `vip_ucretsiz` veya erken erişimde geçer. Ücretli bölüm `ChapterUnlocker` ile açılır: kullanıcı satırı kilitlenir, tek `chapter_unlocks` kaydı ve `unlock:{user}:{chapter}` borç anahtarı yazılır.
- Erken erişim süresi dolunca fiyat da kalkar; bölüm herkese açıktır. Erken erişim sürerken unlock kaydı kilidi açmaz.
- Görsel adresi yetkiden sonra en fazla 5 dakika (`CONTENT_PAGE_URL_TTL`). Yalnızca S3/R2 adaptörü `temporaryUrl` üretir. Yerel disk düz URL vermez; imzalı `reader.media` ile dosyayı özel olarak basar.
- Kodlar düz saklanmaz. Normalize (boşluk, tire, küçük harf, 0/O 1/I L/I) sonrası HMAC-SHA256 (`CODE_HMAC_KEY`). Son 4 hane arama içindir. Hatalı, süresi dolmuş ve iptal kod aynı "Kod kullanılamadı." der.
- Ödeme geçidi yok. Kodlar Sonteklif'te satılır. Cüzdan linki admin `site_settings.sonteklif_url`, yoksa `SONTEKLIF_URL`.
- Okuma hız anahtarı girişliyse `u:{id}`, değilse `ip:{CF-Connecting-IP}`. Referer yoksa hotlink engeli uygulanmaz (doğrudan açılış).
- Filigran piksele gömülmez. Açıkken okuyucu JSON’unda ve `alt` metninde `ruhscans #{id}` görünür.
- Admin süper kullanıcıdır (`Gate::before`). Çeviri ekibi ekonomi, üye ve sistem kaynaklarına giremez; şikayet kuyruğuna girebilir.
- Düz kod yalnızca `code_exports` dosyasında, indirme veya süre dolunca silinir. DB’de düz kod yoktur. 10.000 ve üzeri `GenerateCodeBatch` kuyruğundadır.
- Manuel taş ve VIP yalnızca `MemberAdmin` üzerinden, zorunlu gerekçeyle, ledger ve aktivite günlüğüne yazılır.
- Şikayet "çözüldü" ayrı bir enum değildir; `ReportStatus::Incelendi` kullanılır.
- `ContentSeeder` yalnızca `local` ortamda çağrılır.

## Ortam değişkenleri

`APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_DEFAULT_REGION`, `R2_BUCKET`, `R2_URL`, `R2_ENDPOINT`, `R2_USE_PATH_STYLE_ENDPOINT`, `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `CONTENT_IMAGE_DISK`, `CONTENT_WEBP_QUALITY`, `CONTENT_MAX_ZIP_KB`, `CONTENT_MAX_IMAGE_KB`, `CONTENT_MAX_PAGE_HEIGHT`, `CONTENT_MAX_PAGE_WIDTH`, `CONTENT_MAX_COVER_KB`, `CONTENT_COVER_MIN_WIDTH`, `CONTENT_COVER_MIN_HEIGHT`, `CONTENT_COVER_MAX_WIDTH`, `CONTENT_COVER_MAX_HEIGHT`, `CONTENT_DIRECT_UPLOAD_THRESHOLD_KB`, `CONTENT_DIRECT_UPLOAD_TTL`, `CONTENT_MAX_ZIP_FILES`, `CONTENT_MAX_ZIP_UNCOMPRESSED_KB`, `CONTENT_MAX_ZIP_RATIO`, `CONTENT_COVER_CACHE_TTL`, `CATALOG_HOME_TTL`, `CATALOG_TAGS_TTL`, `CONTENT_SEARCH_SIMILARITY`, `CONTENT_PAGE_URL_TTL`, `CONTENT_WATERMARK`, `READ_RATE_PER_MINUTE`, `READ_BURST_WINDOW`, `READ_BURST_MAX`, `READ_BLOCK_MINUTES`, `HOTLINK_HOSTS`, `TRUSTED_PROXIES`, `SECURITY_HSTS`, `CODE_HMAC_KEY`, `SONTEKLIF_URL`, `TURNSTILE_ENABLED`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`.

## Bilinen eksikler

- `CODE_HMAC_KEY` boşsa kod üretimi ve kullanımı reddedilir. Anahtar yalnızca `.env` içindedir.
- Yasal metinler taslak; hukuk incelemesi yok. Şikayet ve DMCA formları çalışır.
- Windows'ta Horizon `pcntl` / `posix` olmadan çalışmaz. Üretim Linux olmalı.
- R2 anahtarları boşken dosya yüklenemez.
- İlk admin `.env` doldurulunca seed edilir ve doğrudan `/admin` üzerinden giriş yapar; 2FA kurulumu yok.
- `read_blocked_until` için ayrı admin formu yok; blok önbellek ve kolon üzerinden uygulanır.
- PostgreSQL arama indeksi bu ortamda çalıştırılmadı; migration Postgres sürücüsünde `pg_trgm` ve GIN indeksini kurar.
- R2 boşken sayfa yüklemesi `CONTENT_IMAGE_DISK=local` ile yerel diske düşürülebilir.
- `ReportStatus` içinde ayrı "çözüldü" değeri yok; kuyruk `Incelendi` yazar.
- Cloudflare istek gövdesi yaklaşık 100 MB. 100 MB üstü ZIP'ler doğrudan R2'ye yüklenir; create/edit chapter formlarında büyük ZIP alanı henüz yok, retry aksiyonunda kullanılabilir.
- Sayfa küçük resmi local diskte geçici URL üretemez; R2/S3 `temporaryUrl` ister.
- Horizon menüsü `/horizon` bağlantısıdır. Windows geliştirmede `pcntl` yoktur.

## Canlıya almadan önce

- `.env` commit edilmez. `APP_KEY`, `CODE_HMAC_KEY`, veritabanı, R2 ve Turnstile anahtarları üretimde üretilir.
- `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL=https://ruhscans.com`.
- PostgreSQL 16'da `php artisan migrate --force`. `pg_trgm` ve GIN arama indeksi ilk migration ile kurulur; iki yeni migration `users.two_factor_*` sütunlarını ve `translator_groups` tablosu/foreign key'lerini düşürür (`down` geri kurar).
- Redis ayakta. `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`. Horizon yalnızca Linux'ta.
- Turnstile için `TURNSTILE_ENABLED=true` ve gerçek site/secret anahtarları `.env` içinde; test anahtarları yalnızca geliştirmede.
- Yasal metinler (kullanım, gizlilik, DMCA, 18+ uyarı) hukuk incelemesinden geçer. Şu an taslaktır.
- Cloudflare: HSTS, gerçek IP (`TRUSTED_PROXIES`), R2 bucket ve `HOTLINK_HOSTS`.
- `php artisan optimize` sonrası `php artisan up`. Bakım sayfası `errors.503`.
- Sonteklif bağlantısı panelden (`site_settings.sonteklif_url`) doğrulanır.

## Sonraki adım

Canlıda `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan optimize` ve kuyruk işçisini yeniden başlat. Web PHP `lsphp83` için `upload_max_filesize`/`post_max_size` en az 100 MB, R2 bucket CORS'unda `ETag` expose edilmiş olmalı. Turnstile anahtarları `.env` içinde tanımlanır; gerçek anahtarlar commit edilmez. Panel erişimi için 2FA kurulumu gerekmez; engelli olmayan `admin`/`ceviri_ekibi` doğrudan giriş yapar.
