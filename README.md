# Ruhscans

Türkçe, 18+ manga ve manhwa okuma sitesi. Bu depo Faz 1 temelini içerir: kimlik, roller, yönetici 2FA, yaş kapısı ve yerel geliştirme ortamı.

Laravel 11 istendi. 11.x satırı güvenlik duyuruları nedeniyle Composer tarafından kurulamadı. Proje yamalı **Laravel 12.73** ve **PHP 8.3** üzerindedir. Uygulama API'si Laravel 11 ile aynı çizgidedir.

## Gerekenler

- PHP 8.3 (pdo_pgsql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, redis veya predis)
- Composer 2
- Node.js 20+
- Docker Desktop (PostgreSQL 16, Redis, Mailpit)

Windows'ta `php` komutu 8.1 ise PHP 8.3'ün tam yolunu kullanın. Örnek:

`C:\Users\<kullanıcı>\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`

## Yerel kurulum

1. Depoyu klonlayın ve klasöre girin.
2. `copy .env.example .env` (PowerShell: `Copy-Item .env.example .env`).
3. `composer install`
4. `php artisan key:generate`
5. `docker compose -f docker-compose.dev.yml up -d`
6. `.env` içinde PostgreSQL, Redis ve Mailpit değerlerinin örnek dosyayla aynı kaldığını kontrol edin.
7. `php artisan migrate --seed`
8. İlk yönetici için `.env` içine `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` yazıp `php artisan db:seed --class=Database\\Seeders\\AdminSeeder` çalıştırın.
9. `npm install` ve `npm run dev`
10. Ayrı bir terminalde `php artisan serve`
11. Kuyruk için ayrı terminalde `php artisan horizon` (Windows'ta `pcntl` olmadığı için üretimde Linux kullanın; yerelde `php artisan queue:work redis` yeter).

Adresler:

- Site: http://localhost:8000
- Yönetim: http://localhost:8000/admin
- Posta kutusu (Mailpit): http://localhost:8025

## Test

```bash
php artisan test
```

Testler bellek içi SQLite kullanır. PostgreSQL'in ayakta olması gerekmez.

## Sırlar

`.env` depoya girmez. Cloudflare R2 anahtarları, uygulama anahtarı ve ilk yönetici parolası yalnızca ortam değişkenlerindedir.
