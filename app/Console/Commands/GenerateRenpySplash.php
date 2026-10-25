<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateRenpySplash extends Command
{
    protected $signature = 'renpy:splash';

    protected $description = 'Ren\'Py açılış dosyasını üretir';

    public function handle(): int
    {
        $site = (string) config('app.name');
        $api = rtrim((string) config('app.url'), '/').'/api/v1';
        $register = rtrim((string) config('app.url'), '/').'/register';

        File::ensureDirectoryExists(storage_path('app/renpy'));
        File::put(storage_path('app/renpy/zz_splash.rpy'), $this->script($site, $api, $register));
        File::put(storage_path('app/renpy/README.md'), $this->readme($site));

        $this->info('storage/app/renpy/zz_splash.rpy yazıldı.');

        return self::SUCCESS;
    }

    private function script(string $site, string $api, string $register): string
    {
        $site = str_replace(['"', "\n"], '', $site);
        $api = str_replace(['"', "\n"], '', $api);
        $register = str_replace(['"', "\n"], '', $register);

        return <<<RPY
# {$site} açılış kapısı.
# Caydırıcıdır. Dosyayı silen kişi kapıyı atlatır.
# renpy.fetch: Ren'Py 8.2 / 7.7 ve sonrası (https://www.renpy.org/doc/html/fetch.html).
# Daha eski 7.x için urllib yedeği. Mevcut splashscreen etiketine dokunulmaz.

init -999 python:
    import json

    if "rt_offline_days" not in persistent.__dict__:
        persistent.rt_offline_days = 7

init python:
    RT_SITE = "{$site}"
    RT_API = "{$api}"
    RT_REGISTER = "{$register}"

    def rt_message(kind):
        return {
            "timeout": "Bağlantı zaman aşımına uğradı. Yeniden dene.",
            "dns": "Site adı çözülemedi. İnternetini kontrol et.",
            "tls": "Güvenli bağlantı kurulamadı.",
            "http": "Sunucu şu an cevap veremiyor.",
            "json": "Sunucu yanıtı okunamadı.",
            "offline": "Çevrimdışısın. Kayıtlı girişinle devam edebilirsin.",
            "login": "Giriş yapılamadı.",
            "expired": "Oturumun doldu. Yeniden gir.",
        }.get(kind, "Bağlantı kurulamadı.")

    def rt_post(path, payload, token=None):
        headers = {"Content-Type": "application/json", "Accept": "application/json"}
        if token:
            headers["Authorization"] = "Bearer " + token
        body = json.dumps(payload).encode("utf-8")
        url = RT_API + path
        try:
            if hasattr(renpy, "fetch"):
                raw = renpy.fetch(url, method="POST", data=body, headers=headers, timeout=10, result="text")
            else:
                raw = rt_urllib(url, body, headers)
            return json.loads(raw or "{}"), None
        except Exception as error:
            text = str(error).lower()
            if "timed out" in text or "timeout" in text:
                kind = "timeout"
            elif "gaierror" in text or "name or service not known" in text or "nodename" in text:
                kind = "dns"
            elif "certificate" in text or "ssl" in text:
                kind = "tls"
            elif "json" in text or "expecting value" in text:
                kind = "json"
            elif any(code in text for code in ("500", "502", "503", "504")):
                kind = "http"
            else:
                kind = "offline"
            return None, kind

    def rt_urllib(url, body, headers):
        import urllib.request
        request = urllib.request.Request(url, data=body, headers=headers)
        response = urllib.request.urlopen(request, timeout=10)
        return response.read().decode("utf-8")

    def rt_offline_ok():
        seen = getattr(persistent, "rt_seen", None)
        if seen is None:
            return False
        import time
        days = getattr(persistent, "rt_offline_days", 7) or 0
        return (time.time() - seen) < days * 86400

        def rt_greet(data):
         if not isinstance(data, dict):
          return
         name = data.get("name") or ""
         balance = data.get("coin_balance")
         persistent.rt_name = name
         if balance is None:
          persistent.rt_balance = None
          return
         try:
          persistent.rt_balance = int(balance)
         except Exception:
          persistent.rt_balance = None

label rt_gate:
    python:
        rt_error = ""
        rt_data = None
        if persistent.rt_token:
            rt_data, rt_kind = rt_post("/verify", {}, persistent.rt_token)
            if rt_data is None and rt_offline_ok():
                rt_kind = None
            elif rt_data is None:
                rt_error = rt_message(rt_kind)
                persistent.rt_token = None
            elif not rt_data.get("valid"):
                persistent.rt_token = None
                rt_error = rt_message("expired")
            else:
                import time
                persistent.rt_seen = time.time()
            rt_greet(rt_data)

    if persistent.rt_token and not rt_error:
        return

    show screen rt_splash
    pause 2.4
    hide screen rt_splash
    call screen rt_login(error=rt_error)
    return

label rt_ready:
    return

screen rt_splash():
    modal True
    if renpy.loadable("rt_logo.png"):
        add "rt_logo" align (0.5, 0.38)
    text "[RT_SITE] tarafından Türkçeleştirilmiştir" align (0.5, 0.62) size 28


screen rt_hello():
 modal True
 frame:
  align (0.5, 0.5)
  xsize 520
  vbox:
   spacing 12
   if persistent.rt_name:
    text "Hoş geldin, [persistent.rt_name]" size 28
   else:
    text "Hoş geldin" size 28
   if persistent.rt_balance is not None:
    text "Bakiye: [persistent.rt_balance] coin" size 22

screen rt_login(error=""):
    modal True
    default email = ""
    default password = ""
    frame:
        align (0.5, 0.5)
        xsize 520
        vbox:
            spacing 16
            text "[RT_SITE] hesabınla gir" size 28
            text "E-posta"
            input value VariableInputValue("email") length 80
            text "Şifre"
            input value VariableInputValue("password") length 80 masked True
            textbutton "Giriş":
                action Function(rt_submit, email, password)
            textbutton "Hesap oluştur":
                action OpenURL(RT_REGISTER)
            if error:
                text error color "#FF4D8D"

init python:
    def rt_submit(email, password):
        result, kind = rt_post("/login", {"email": email.strip(), "password": password})
        if result and result.get("token"):
            import time
            persistent.rt_token = result["token"]
            persistent.rt_seen = time.time()
            rt_greet(result)
            renpy.hide_screen("rt_login")
            if persistent.rt_name or persistent.rt_balance is not None:
                renpy.call_screen("rt_hello")
            renpy.jump("rt_ready")
        message = rt_message(kind or "login")
        if isinstance(result, dict) and result.get("message"):
            message = result["message"]
        renpy.show_screen("rt_login", error=message)
        renpy.restart_interaction()
RPY;
    }

    private function readme(string $site): string
    {
        return <<<MD
# {$site} Ren'Py kapısı

Bu dosya oyunun içinde çalışır. Caydırıcıdır; `.rpy` dosyasını silen kişi kapıyı atlatır.

## Sürüm

`renpy.fetch` Ren'Py **8.2 ve 7.7** ile geldi (27 Ocak 2024 sürüm notu, [fetch belgesi](https://www.renpy.org/doc/html/fetch.html)).
8.2 / 7.7 ve sonrası `renpy.fetch` kullanır. 7.6 ve daha eski sürümlerde fonksiyon yoktur; betik `urllib.request` yedeğine düşer.
Başarısızlık `FetchError` fırlatır; metin oyuncuya gösterilmez, aşağıdaki Türkçe tabloya çevrilir.

| Ağ durumu | Oyuncu görür | Oyun |
| --- | --- | --- |
| Zaman aşımı | Bağlantı zaman aşımına uğradı. Yeniden dene. | Açık kalır |
| DNS | Site adı çözülemedi. İnternetini kontrol et. | Açık kalır |
| Sertifika | Güvenli bağlantı kurulamadı. | Açık kalır |
| 5xx | Sunucu şu an cevap veremiyor. | Açık kalır |
| Bozuk JSON | Sunucu yanıtı okunamadı. | Açık kalır |
| Çevrimdışı, süre içinde | Kayıtlı girişle devam | Oyuna girer |
| Çevrimdışı, süre doldu | Çevrimdışısın. Kayıtlı girişinle devam edebilirsin. | Giriş ister |
| Token geçersiz | Oturumun doldu. Yeniden gir. | Giriş ister |

Çevrimdışı tolerans `persistent.rt_offline_days` (varsayılan 7). Oyuncu bunu kendi kaydında değiştirebilir.

## Kurulum

1. `zz_splash.rpy` dosyasını `game/` klasörüne koy. `zz_` öneki, oyunun kendi betiklerinden sonra yüklenir.
2. İsteğe bağlı logo: `game/rt_logo.png`. Yoksa yalnız yazı çıkar; oyun açılır.
3. API yolu sabittir: `/api/v1`. Eski oyunlar bu yolu kullanmaya devam eder.

## splashscreen çakışması

Oyunda zaten `label splashscreen` varsa bu dosya o etiketi **tanımlamaz** ve kendi etiketine `rt_gate` der.
O zaman oyunun `splashscreen` etiketinin sonuna tek satır ekle:

```
jump rt_gate
```

Bu satır yoksa kapı çalışmaz ama oyun kendi açılışıyla açılır. Asla iki `splashscreen` tanımı olmaz; çift tanım oyunu açılmaz yapardı.

## Deneme

`renpy-test/` boş bir Ren'Py 8 iskeletidir. `game/` içine bu dosyayı kopyala, SDK ile aç, giriş ekranının çıktığını gör.
MD;
    }
}
