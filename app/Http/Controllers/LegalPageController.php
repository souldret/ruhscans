<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalPageController extends Controller
{
    /**
     * @var array<string, array{title: string, paragraphs: list<string>}>
     */
    private array $pages = [
        'kullanim-sartlari' => [
            'title' => 'Kullanım şartları',
            'paragraphs' => [
                'Ruhscans, kullanıcıların yayımlanan manga ve manhwa bölümlerini kişisel ve ticari olmayan amaçla okuması için sunulur. Hesap oluşturarak bu şartları kabul etmiş sayılırsınız.',
                'Ruh taşı ve VIP süreleri sitede satılmaz. Yönetici tarafından üretilen kodlar üçüncü taraf bir vitrinde satılabilir; kodun siteye girilmesi bakiyeyi veya süreyi hesaba ekler. Kodlar devredilemez, nakde çevrilemez ve kullanılmış kodlar iade edilmez.',
                'Hesabınızın güvenliğinden siz sorumlusunuz. Siteyi otomatik kazıma, hizmeti aksatma veya başkasının hesabına izinsiz erişim için kullanamazsınız.',
                'Bu metin taslaktır ve yayına alınmadan önce hukuk danışmanıyla gözden geçirilmelidir.',
            ],
        ],
        'gizlilik' => [
            'title' => 'Gizlilik',
            'paragraphs' => [
                'Hesap oluştururken ad, e-posta ve parola özeti tutulur. Oturum, güvenlik ve hız sınırları için IP adresi ile tarayıcı bilgisi kısa süreli işlenebilir.',
                'Ödeme kartı bilgisi toplanmaz. Ruh taşı bakiyesi, VIP bitiş tarihi ve kod kullanım kayıtları hizmeti işletmek için saklanır.',
                'Çerezler oturum, yaş onayı ve güvenlik için kullanılır. Yaş onayı çerezi 30 gün tutulur. Zorunlu olmayan izleme çerezi bu fazda yoktur.',
                'Verilerinize erişim veya silme talebi için şikayet sayfasındaki kanal kullanılacaktır. Bu metin taslaktır.',
            ],
        ],
        'dmca' => [
            'title' => 'Telif bildirimi',
            'paragraphs' => [
                'Bir eserin izinsiz yayımlandığını düşünüyorsanız eser adı, ihlal edilen adres, hak sahipliğini gösteren bilgi ve iletişim adresinizle bildirim gönderin.',
                'Eksik bildirimler işleme alınmayabilir. Geçerli bir bildirim incelenir; içerik geçici olarak kaldırılabilir ve yükleyen tarafa itiraz hakkı tanınır.',
                'Kasıtlı olarak yanlış bildirim göndermek sorumluluk doğurabilir. İletişim adresi yayından önce doldurulacaktır. Bu metin taslaktır.',
            ],
        ],
        'age' => [
            'title' => '18+ uyarı',
            'paragraphs' => [
                'Bu sitedeki içerikler 18 yaşından küçükler için uygun olmayabilir. 18 yaşından küçükseniz siteyi kullanmayın ve tarayıcıyı kapatın.',
                '“18 yaşından büyüğüm” seçeneği, onayınızı 30 gün boyunca bir çerezde saklar. Onaysız ziyaretçilere katalog ve okuma sayfaları gösterilmez.',
                'Ebeveynler cihazlarında yaş filtreleri kullanmalıdır. Bu metin taslaktır.',
            ],
        ],
        'sikayet' => [
            'title' => 'Şikayet',
            'paragraphs' => [
                'İçerik, hesap veya telif şikayetlerinizi bu sayfa üzerinden iletebilirsiniz. Şikayet formunun kalıcı adresi sonraki fazda eklenecektir.',
                'Bildirimde sayfa adresi, kısa açıklama ve size dönüş yapılabilecek bir e-posta bulunmalıdır. Acil güvenlik sorunları öncelikli incelenir.',
                'Bu sayfa yasal bildirim kanalının taslağıdır ve henüz bir posta kutusu bağlı değildir.',
            ],
        ],
    ];

    public function terms(): View
    {
        return $this->show('kullanim-sartlari');
    }

    public function privacy(): View
    {
        return $this->show('gizlilik');
    }

    public function age(): View
    {
        return $this->show('age');
    }

    private function show(string $page): View
    {
        return view('legal.show', $this->pages[$page]);
    }
}
