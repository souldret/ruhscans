# Cloudflare WAF — kod deneme saldırısı

Kod kullanma zaten uygulamada kilitli: hesap başına dakikada 5 deneme, hesap veya IP başına saatte 20 hatalı denemeden sonra bir saat kilit. WAF bunu uygulamaya ulaşmadan keser.

Cloudflare → Security → WAF → Custom rules. Sırayla ekle.

1. Kod kullanma hızı
   - Alan: `http.request.uri.path` equals `/hesap/kod-kullan`
   - Rate: IP başına 10 istek / 1 dakika
   - Eylem: Block, süre 1 saat

2. Giriş hızı
   - Alan: `http.request.uri.path` equals `/login` ve method POST
   - Rate: IP başına 10 istek / 5 dakika
   - Eylem: Managed Challenge

3. Kayıt hızı
   - Alan: `http.request.uri.path` equals `/register` ve method POST
   - Rate: IP başına 5 istek / 10 dakika
   - Eylem: Managed Challenge

4. API giriş
   - Alan: `http.request.uri.path` equals `/api/v1/login`
   - Rate: IP başına 20 istek / 1 dakika
   - Eylem: Block

5. İndirme
   - Alan: `http.request.uri.path` starts with `/indir/`
   - Rate: IP başına 60 istek / 5 dakika
   - Eylem: Managed Challenge

6. Tek kullanımlık posta
   - Alan: `http.request.uri.path` equals `/register`
   - Ve gövde `mailinator.com`, `guerrillamail.com`, `yopmail.com`, `10minutemail.com` içeriyor
   - Eylem: Block

Turnstile anahtarları `.env` içinde:

```
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

İkisi de boşken widget görünmez ve kayıt çalışır. Doldurulunca kayıt ve giriş formu doğrulama ister.

İmzalı indirme linkini isteyen IP’ye bağlamak için:

```
BIND_DOWNLOAD_IP=true
```

Aynı hesabın aynı anda açık oturum sayısı:

```
MAX_SESSIONS=2
```
