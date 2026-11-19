# Ruhscans

Türkçe, 18+ manga ve manhwa okuma sitesi. Ödeme geçidi yoktur. Ruh taşı ve VIP, Sonteklif kodlarıyla yüklenir.

Laravel 11 istendi. 11.x satırı güvenlik duyuruları nedeniyle Composer tarafından kurulamadı. Proje yamalı **Laravel 12.73** ve **PHP 8.3** üzerindedir. Uygulama API'si Laravel 11 ile aynı çizgidedir.

## Mimari

İnce controller. İş kuralları `app/Services` altında:

- `Catalog` — ana sayfa şeritleri, keşfet sorgusu, Redis önbelleği (yalnızca kimlikler; kara liste bellekten uygulanır)
- `Content` — ZIP içe aktarma, WebP, webtoon kesimi, Postgres araması
- `Reader` — bölüm erişimi ve kısa ömürlü sayfa adresi
- `Economy` — cüzdan defteri, kod üretimi ve kullanımı, bölüm açma, VIP
- `Security` — okuma hızı, hotlink, filigran, istemci adresi
- `Seo` — Türkçe meta, schema.org, sayfalanmış sitemap
- `Ops` — site ayarları, bakım modu, yönetici özeti

Şema yalnızca migration ile değişir. Bakiye ve okuma bloğu fillable değildir; ledger `lockForUpdate` ile yazılır. Arayüz Türkçe ve mobil önceliklidir. Sırlar yalnızca `.env` içindedir.

## Gerekenler

- PHP 8.3 (pdo_pgsql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, gd)
- Composer 2
- Node.js 20+
- Docker Desktop (PostgreSQL 16, Redis 7, Mailpit)

Redis eklentisi (`phpredis`) şart değildir. Varsayılan istemci Predis'tir. Windows'ta `php` komutu 8.1 ise PHP 8.3'ün tam yolunu kullanın:

`C:\Users\<kullanıcı>\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`

## Yerel kurulum

1. Depoyu klonlayın ve klasöre girin.
2. `copy .env.example .env` (PowerShell: `Copy-Item .env.example .env`).
3. `composer install`
4. `php artisan key:generate`
5. `.env` içinde `CODE_HMAC_KEY` için uzun rastgele bir değer üretin.
6. `docker compose -f docker-compose.dev.yml up -d`
7. `php artisan migrate --seed`
8. İlk yönetici için `.env` içine `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` yazıp `php artisan db:seed --class=Database\\Seeders\\AdminSeeder` çalıştırın.
9. `npm install` ve `npm run build` (geliştirmede `npm run dev`)
10. `php artisan serve`
11. Kuyruk: `php artisan queue:work redis`. Windows'ta Horizon `pcntl` olmadığı için çalışmaz; üretimde Linux ve `php artisan horizon` kullanın.

Adresler:

- Site: http://localhost:8000
- Yönetim: http://localhost:8000/admin
- Posta kutusu (Mailpit): http://localhost:8025

İlk açılışta yaş kapısı çıkar. Yönetici, TOTP onaylamadan panele giremez.

## Komutlar

```bash
php artisan migrate
php artisan db:seed
php artisan test
php artisan optimize
php artisan optimize:clear
php artisan down --render=errors.503 --retry=60
php artisan up
vendor/bin/pint
npm run dev
npm run build
```

`php artisan optimize` config, route, view ve olay önbelleğini kurar. Config dosyaları birbirinin `config()` çağrısını kullanmaz. Testler bellek içi SQLite kullanır; PostgreSQL ve Redis ayakta olmak zorunda değildir. Uyarı görmemek için `.env` dosyasının var olması yeter.

## Önbellek

Ana sayfa şeritleri `catalog.home` (`CATALOG_HOME_TTL`, varsayılan 300 sn), etiket bulutu `catalog.tags` (`CATALOG_TAGS_TTL`, varsayılan 900 sn). Seri, bölüm veya etiket kaydı önbelleği düşürür. Yönetici özeti `admin.digest` altında 60 saniye tutulur. Site ayarları `Cache::rememberForever` ile okunur ve yazılınca yenilenir.

Geniş sayfa görselleri `CONTENT_MAX_PAGE_WIDTH` (varsayılan 1600) değerine ölçeklenir, sonra webtoon kesilir. Çıktı WebP, kalite 82.

## SEO

- `/robots.txt` yönetim, cüzdan, kütüphane ve sayfa adreslerini kapatır.
- `/sitemap.xml` ve `/sitemap-{n}.xml` gizli seri ile yayımlanmamış bölümü dışlar. Sayfa başına 1000 katalog adresi; ilk sayfada sabit yasal adresler de vardır.
- Seri sayfası canonical, nötr `og-adult.svg` ve Book/ComicSeries şeması taşır. Gerçek kapak Open Graph'a yazılmaz.
- Bölüm sayfası `noindex, follow` ve ComicIssue şeması taşır.
- 404, 403, 500 ve 503 sayfaları gece temasında ve 18+ uyarılıdır.
