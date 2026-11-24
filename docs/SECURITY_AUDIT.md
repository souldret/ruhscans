# Ruhscans Güvenlik Denetimi Raporu

Tarih: 2026-11-24  
Kapsam: Laravel 12 + Livewire 3 + Filament 3 + PostgreSQL 16 + Redis + Cloudflare R2 + Cloudflare + OpenLiteSpeed  
Yöntem: Saldırgan gözüyle kaynak kod, rota envanteri, ekonomi/ledger bütünlüğü, kimlik doğrulama, girdi/çıktı, yapılandırma, bağımlılık ve git geçmişi denetimi.

---

## A) Route ve API Envanteri

Uygulamada harici `/api` veya `/telescope` rotası bulunmamaktadır. Tüm web rotaları `TrustCloudflareProxies`, `EnsureAdult`, `TrackUserSession` ve `SecurityHeaders` katmanlarından geçer.

| Grup / Rota | Metot | Kimlik | Yetki / Policy | Rate Limit | CSRF | Girdi Doğrulama |
| --- | --- | --- | --- | --- | --- | --- |
| `/`, `/kesfet`, `/seri/{series}`, `/etiket/{tag}` | GET | Herkese açık (18+ çerez) | Gizli seriler 404, kara liste süzülür | Cloudflare + önbellek | N/A | Enum & parametreli sorgu |
| `/18-onay` | POST | Herkese açık | Aynı origin yönlendirme kontrolü | `throttle:30,1` | Evet | `SafeIntendedUrl` |
| `/seri/{series}/bolum/{number}` | GET | Herkese açık | Kilitli bölümde sayfa URL yok | — | N/A | Route model binding |
| `/seri/{series}/bolum/{number}/sayfalar` | GET (JSON) | Herkese açık | `ChapterAccess::gate` + `read_blocked_until` | `ThrottlePageReads` (40/dk, 12/10sn + 15dk blok) + Hotlink | N/A | Route model binding |
| `/sayfa/{page}` | GET | İmzalı URL | `hasValidSignature` + ikinci `ChapterAccess::isLocked` | 5 dk TTL | N/A | Signed route |
| `/seri/{series}/bolum/{number}/ac` | POST | `auth` | `ChapterUnlocker` + `lockForUpdate` | `throttle:20,1` | Evet | Bakiye + kilit doğrulama |
| `/seri/{series}/bolum/{number}/ilerleme` | POST | `auth` | Kilitli bölüm 403, gizli modda yazmaz | `throttle:120,1` | Evet | `page: integer, min:1` |
| `/okuma-modu` | POST | `auth` | Yalnızca kendi hesabı | `throttle:30,1` | Evet | `Rule::enum(ReadingMode)` |
| `/cuzdan`, `/cuzdan/kod` | GET/POST | `auth` | Yalnızca kendi cüzdanı | `throttle:30,1` + `CodeRedeemer` (kullanıcı + IP bazlı 10 hata / 60 dk kilit) | Evet | Turnstile + HMAC format |
| `/kutuphane`, `/okuma-gecmisi`, `/okuma-tercihleri` | GET | `auth` | Yalnızca `auth()->id()` | Livewire + aksiyon bazlı | Evet | `SafePanicUrl`, tag allowlist |
| `/profile`, `/profile/veri-indir`, `/profile/avatar/{user}` | GET | `auth` | Sıkı `auth()->id() === $user->id` + imzalı URL (IDOR yok) | `throttle:5,10` (export) | Evet | `finfo` MIME + boyut |
| `/sikayet`, `/dmca`, `/bildirim` | GET/POST | Herkese açık | Honeypot `website` + Turnstile | `throttle:5,10` | Evet | Uzunluk & URL doğrulama |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | GET/POST | `guest` | Turnstile + enumeration koruması | 5/dk (`RateLimiter`) | Evet | 12+ güçlü şifre kuralı |
| `/admin/*` (Filament) | GET/POST | `auth` | `User::canAccessPanel` (`admin`/`ceviri_ekibi`, engelli değil) + `AdminAccess::admin()` | Panel girişi 5/dk + 10 hatada 15 dk kilit + Turnstile | Evet | Filament form kuralları |
| `/admin/kodlar/indir` | GET | `auth` | Yalnızca engelli olmayan `admin` | `throttle:10,1` | N/A | Atomik tek seferlik token (`lockForUpdate`) |
| `/admin/zip/*` | POST | `auth` | Yalnızca engelli olmayan `admin`/`ceviri_ekibi` | `throttle:30,1` | Evet | Pending prefix + tek kullanımlık token |
| `/horizon/*` | GET/POST | `auth` | `viewHorizon` gate: yalnızca engelli olmayan `admin` | — | Evet | Horizon dahili |

---

## B) Bulgu Özeti ve Uygulanan Düzeltmeler

### 1. [KRİTİK] `CF-Connecting-IP` Sahteciliği ve `TRUSTED_PROXIES=*`
- **Risk**: `bootstrap/app.php` içinde `trustProxies(at: '*')` tanımlıydı ve `TrustCloudflareProxies` gelen bağlantının gerçek soket IP'sini (`REMOTE_ADDR`) doğrulamadan `CF-Connecting-IP` başlığını doğrudan `REMOTE_ADDR` olarak yazıyordu. `TurnstileService` de aynı başlığı doğrudan okuyordu. Doğrudan sunucuya veya başlık enjekte ederek erişen bir saldırgan tüm IP tabanlı hız sınırlarını (giriş, kod kullanma, okuma) atlatabilirdi.
- **Düzeltme**:
  - `config/security.php` varsayılan olarak resmi **Cloudflare IPv4 + IPv6 CIDR aralıklarını** yükler (`*` kabul edilmez).
  - `bootstrap/app.php` içinden wildcard `trustProxies(at: '*')` kaldırıldı.
  - `TrustCloudflareProxies` ve `TurnstileService` yalnızca `REMOTE_ADDR` güvenilir bir Cloudflare CIDR aralığındaysa (`IpUtils::checkIp`) `CF-Connecting-IP` başlığını kabul eder.
  - `.env.example` içindeki `TRUSTED_PROXIES=*` kaldırıldı.

### 2. [YÜKSEK] Kod Kullanımında IP/Hesap Değiştirerek Hız Sınırı Atlatma ve Zamanlama Farkı
- **Risk**: `CodeRedeemer` hız sınırını yalnızca bileşik `user_id:ip` anahtarıyla tutuyordu. Bir saldırgan IP değiştirerek kullanıcı kilidini, hesap değiştirerek IP kilidini atlatabiliyordu. Ayrıca biçimi geçersiz kodlarda HMAC hesaplanmadan erken dönüş yapılıyordu.
- **Düzeltme**:
  - Hem **kullanıcı bazlı** (`kod-hata:u:{id}`, `kod-kilit:u:{id}`) hem **IP bazlı** (`kod-hata:ip:{ip}`, `kod-kilit:ip:{ip}`) çift yönlü hız sınırı ve kilit eklendi.
  - Biçim geçersiz olsa bile sabit maliyetli HMAC hesaplanarak zamanlama farkı azaltıldı.

### 3. [YÜKSEK] Tek Seferlik Kod CSV İndirmesinde Yarış Durumu (Race Condition)
- **Risk**: `/admin/kodlar/indir` rotası `CodeExportStore::pull()` sonrası dosyayı okuyup sonra siliyordu; eşzamanlı iki istek aynı CSV'yi iki kez indirebilirdi.
- **Düzeltme**:
  - `CodeExportStore::pullAndConsume()` ile `DB::transaction` + `lockForUpdate()` altında atomik tek seferlik tüketim eklendi, `Cache-Control: no-store, private` ve `economy` aktivite günlüğü kaydı eklendi.

### 4. [YÜKSEK] Yaş Kapısı Açık Yönlendirme Önek Atlatması (`AgeGateController`)
- **Risk**: `str_starts_with($target, $request->getSchemeAndHttpHost())` kontrolü `https://ruhscans.com.evil.com/phish` gibi önek eşleşen zararlı alan adlarına yönlendirmeye izin veriyordu.
- **Düzeltme**: `AgeGateController::safeIntendedUrl()` ile `parse_url` üzerinden şema, host, port ve `userinfo`/`\\`/`//` doğrulaması eklendi; uymayan her hedef ana sayfaya düşürülür.

### 5. [ORTA] JSON-LD Şemasında `</script>` XSS Vektörü
- **Risk**: `resources/views/components/seo-meta.blade.php` içinde `{!! json_encode($meta['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}` çıktısında `JSON_HEX_TAG` bayrağı yoktu. Başlık/açıklama içinde `</script>` geçmesi halinde script bloğundan çıkılabilirdi.
- **Düzeltme**: `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` bayrakları eklendi.

### 6. [ORTA] Şifre Sıfırlamada Kullanıcı Numaralandırma
- **Risk**: `forgot-password.blade.php` kayıtlı olmayan e-posta için `passwords.user` hatasını döndürerek kayıtlı e-postaların tespitine izin veriyordu.
- **Düzeltme**: Hız sınırı (`RESET_THROTTLED`) dışındaki tüm durumlarda aynı nötr başarı mesajı döndürülür.

### 7. [YÜKSEK] Engellenmiş Adminin `Gate::before` ve `viewHorizon` Atlatması + Eksik Rota Hız Sınırları
- **Risk**: `AppServiceProvider` içindeki `Gate::before` ve `HorizonServiceProvider` içindeki `viewHorizon` engellenmiş bir adminin (`blocked_at !== null`) yetkilerini kesmiyordu; ayrıca `reader.unlock`, `reader.progress`, `reader.mode`, `age-gate.confirm`, `admin.zip.direct.*` ve `admin.codes.csv` rotalarında rota düzeyi `throttle` yoktu.
- **Düzeltme**: Hem `AppServiceProvider::boot()` `Gate::before` hem `HorizonServiceProvider::gate()` içine `! $user->isBlocked()` eklendi; tüm ilgili POST/admin uç noktalarına uygun `throttle` kuralları eklendi.

### 8. [ORTA] Oturum Çerezi `Secure` Varsayılanı, CSP `object-src 'none'` ve COOP
- **Risk**: `config/session.php` içinde `SESSION_SECURE_COOKIE` tanımlanmazsa `null` kalabiliyordu; CSP'de `object-src 'none'` ve yanıtta `Cross-Origin-Opener-Policy` eksikti.
- **Düzeltme**: Üretim ortamında `secure` otomatik `true` yapıldı; CSP'ye `object-src 'none'` ve `SecurityHeaders`'a `Cross-Origin-Opener-Policy: same-origin` eklendi.

### 9. [ÖNLEM] Ledger Tutarlılık Doğrulama Komutu (`economy:verify`)
- `app/Console/Commands/VerifyEconomyLedger.php` eklendi ve `routes/console.php` içinde saatlik (`hourly()->withoutOverlapping()`) zamanlandı.
- Tüm kullanıcıların `ruh_tasi_bakiye` değerini `wallet_transactions` toplamıyla karşılaştırır; tutarsızlık veya negatif bakiye tespitinde `Log::critical` ve `economy-audit` aktivite günlüğü kaydı yazar.

---

## C) Bağımlılık, Kod ve Git Geçmişi Taraması

- **npm audit (`npm audit --omit=dev`)**: **0 güvenlik açığı**.
- **Composer**: Yerel ortamda `composer` ikilisi PATH'te değil; canlı/CI ortamında `composer audit` çalıştırılmalıdır (aşağıdaki canlı komutlara eklendi).
- **Git geçmişi ve sır taraması**: `.env` veya `.env.production` hiçbir commit'te bulunmadı; kaynak kodda sabit kodlanmış AWS/R2 anahtarı, özel anahtar veya token tespit edilmedi. `.env.example` yalnızca boş veya yerel örnek değerler içerir.
- **SQL & Mass Assignment**: Tüm `whereRaw` çağrıları (`SeriesSearch`) parametreli binding kullanır; `User::$fillable` içinde `ruh_tasi_bakiye` ve `read_blocked_until` yoktur (bakiye yazımı `User::$balanceWriteAllowed` bayrağı ve PostgreSQL `CHECK (ruh_tasi_bakiye >= 0)` ile korunur).
- **R2 & Görseller**: `r2` diski `visibility => private`; bölüm sayfaları yalnızca `ChapterAccess` sonrası en fazla 5 dakikalık presigned URL (veya yerelde imzalı `reader.media` + ikinci yetki kontrolü) ile servis edilir. Kapaklar ve avatarlar ayrı öneklerde (`covers/...`, `avatars/{user_id}/...`) saklanır.

---

## D) Canlı Kontrol Betiği

`scripts/security-smoke.sh` eklendi. Örnek kullanım:

```bash
bash scripts/security-smoke.sh https://ruhscans.com <kilitli-seri-slug> <kilitli-bolum-no>
```

---

## E) Sunucu Yapılacaklar (OpenLiteSpeed + Cloudflare + PostgreSQL + Redis + R2)

Kod tarafında çözülemeyen, doğrudan sunucu ve Cloudflare panelinde doğrulanması gereken maddeler:

1. **Origin IP Gizleme (Cloudflare Only Firewall)**: VPS güvenlik duvarında (UFW/iptables) 80/443 portlarını **yalnızca Cloudflare IP aralıklarına** açık bırakın; böylece saldırganlar Cloudflare'i atlayıp doğrudan VPS'e bağlanamaz.
2. **OpenLiteSpeed Hassas Dosya Engeli**: `public/` dışındaki tüm dizinlerin (`.env`, `.git`, `storage`, `vendor`, `bootstrap`, `config`) web kökü dışında olduğundan ve `.htaccess` / vhost kurallarıyla `/.env`, `/.git/*` erişimlerinin `403`/`404` döndürdüğünden emin olun.
3. **Üretim `.env` Doğrulaması**:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ruhscans.com`
   - `SESSION_DRIVER=redis`, `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`
   - `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
   - `TURNSTILE_ENABLED=true` ve gerçek site/secret anahtarları
   - `CODE_HMAC_KEY` güçlü, benzersiz 64+ karakter rastgele anahtar
   - `TRUSTED_PROXIES` boş bırakılabilir (varsayılan Cloudflare CIDR listesini kullanır)
   - `HOTLINK_HOSTS=ruhscans.com,www.ruhscans.com`
4. **Cloudflare R2 Bucket Politikası**:
   - Bölüm sayfalarının bulunduğu bucket'ta **Public Access / R2.dev kapalı** ve dizin listelemesi kapalı olsun; sayfalar yalnızca presigned URL ile açılsın.
   - Kapaklar için ayrı genel CDN yolu (`R2_URL`) kullanılıyorsa yalnızca kapak/avatar öneklerine izin veren Cloudflare WAF/Transform kuralı uygulayın; `pages/` veya `chapter-zips/` önekleri genel CDN'den kesinlikle engelli olsun.
5. **Cron & Horizon**:
   - Sistem cron'unda `* * * * * cd /path/to/ruhscans && php artisan schedule:run >> /dev/null 2>&1` aktif olmalı ki `economy:verify` her saat otomatik çalışsın.
   - Horizon supervisor/systemd altında yalnızca Linux sunucuda çalışmalı.
6. **Bağımlılık Denetimi**: Sunucuda veya CI'da `composer audit` komutunu düzenli çalıştırın.
