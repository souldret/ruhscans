<?php

namespace Database\Seeders;

use App\Enums\CoinCodeStatus;
use App\Enums\Platform;
use App\Enums\TranslationStatus;
use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use App\Models\Download;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use App\Support\Slug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->placeholders();

        $admin = User::factory()->admin()->create([
            'name' => 'Yönetici',
            'email' => 'admin@ruhscans.test',
        ]);

        $member = User::factory()->create([
            'name' => 'Deneme Üye',
            'email' => 'uye@ruhscans.test',
        ]);

        $vip = User::factory()->rich()->create([
            'name' => 'Coin Üye',
            'email' => 'vip@ruhscans.test',
        ]);

        $tags = collect([
            'Romantik', 'Korku', 'Fantastik', 'Bilim Kurgu',
            'Komedi', 'Dram', 'Gizem', 'Macera',
            'Okul', 'Doğaüstü', 'Suç', 'Tarih',
        ])->map(fn (string $name) => Tag::query()->create([
            'name' => $name,
            'slug' => Slug::from($name),
        ]));

        $titles = [
            'Gümüş Bahçe', 'Gece Vapuru', 'Kırık Ayna', 'Yıldız Çarşısı',
            'Sessiz Koridor', 'Amber Mektup', 'Son Peron', 'Mavi Çatı',
            'Kayıp Defter', 'İnce Yağmur', 'Eski Anahtar', 'Puslu Liman',
            'Kızıl Saksı', 'Boş Perde', 'Uzun Gölge', 'Cam Balkon',
            'Islak Sokak', 'Beyaz İstasyon', 'Küçük Fırtına', 'Altın Bilet',
            'Kara Defter', 'Yavaş Tren', 'Eski Radyo', 'Mühürlü Oda',
            'Soluk Fotoğraf', 'Gece Nöbeti', 'Kırık Saat', 'Sıcak Çay',
            'Kapalı Vitrin', 'Uzak Ada', 'İnce Çizgi', 'Son Prova',
            'Mavi Mürekkep', 'Kayıp İstasyon', 'Sessiz Sahne', 'Eski Bilet',
            'Puslu Merdiven', 'Kırık Fener', 'Yıldız Tozu',
            'Gece Yarısı Kütüphanesinde Kaybolan Son Mektubun Uzun Adı',
        ];

        $platforms = [Platform::Windows, Platform::Android, Platform::Patch, Platform::Mac, Platform::Linux];

        foreach ($titles as $index => $title) {
            $missingArt = $index === 1;
            $game = Game::factory()->create([
                'title' => $title,
                'slug' => Slug::from($title),
                'coin_price' => $index % 2 === 0 ? 0 : 100,
                'translation_status' => $index % 3 === 0 ? TranslationStatus::Complete : TranslationStatus::Ongoing,
                'translation_percent' => $index % 3 === 0 ? 100 : (($index * 13) % 90) + 5,
                'cover_path' => $missingArt ? null : 'placeholders/cover.svg',
                'banner_path' => $missingArt ? null : 'placeholders/banner.svg',
                'screenshots' => $missingArt ? [] : ['placeholders/shot.svg'],
                'view_count' => $index * 17,
                'download_count' => $index % 5,
                'published_at' => now()->subDays(40 - $index),
            ]);

            $game->tags()->attach($tags->random(random_int(1, 3))->pluck('id'));

            foreach (array_slice($platforms, 0, 1 + ($index % 3)) as $platform) {
                $path = 'games/'.$game->slug.'-'.$platform->value.'.zip';
                Storage::disk('local')->put($path, 'yama');
                $game->files()->create([
                    'label' => $platform->label(),
                    'platform' => $platform,
                    'storage_path' => $path,
                    'size_bytes' => $game->size_mb * 1024 * 1024,
                    'version' => $game->game_version,
                ]);
            }
        }

        $this->codes($admin, $member, $vip);
        $this->downloads($member, $vip);
    }

    private function codes(User $admin, User $member, User $vip): void
    {
        $plans = [
            ['Yaz 30', 20, 30],
            ['Sezon 90', 20, 90],
            ['Yıl 365', 20, 365],
        ];

        $now = now();

        foreach ($plans as [$name, $quantity, $days]) {
            $batch = CoinCodeBatch::query()->create([
                'name' => $name,
                'quantity' => $quantity,
                'coin_amount' => $days,
                'created_by' => $admin->id,
            ]);

            $rows = [];

            foreach (range(1, $quantity) as $code) {
                $rows[] = [
                    'code' => strtoupper(implode('-', str_split(bin2hex(random_bytes(8)), 4))),
                    'batch_id' => $batch->id,
                    'coin_amount' => $days,
                    'status' => CoinCodeStatus::Unused->value,
                    'used_by' => null,
                    'used_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            CoinCode::query()->insert($rows);

            if ($name === 'Yaz 30') {
                foreach (['MOBL-E2E2-CODE-0001', 'MOBL-E2E2-CODE-0002', 'MOBL-E2E2-CODE-0003', 'DESK-E2E2-CODE-0001', 'DESK-E2E2-CODE-0002', 'DESK-E2E2-CODE-0003'] as $code) {
                    CoinCode::query()->create([
                        'code' => $code,
                        'batch_id' => $batch->id,
                        'coin_amount' => 500,
                        'status' => CoinCodeStatus::Unused,
                    ]);
                }
            }

            $used = CoinCode::query()->where('batch_id', $batch->id)->where('code', 'not like', '%E2E2%')->orderBy('id')->limit(4)->get();

            foreach ($used as $offset => $code) {
                $owner = $offset % 2 === 0 ? $vip : $member;
                $code->forceFill([
                    'status' => $offset === 3 ? CoinCodeStatus::Disabled : CoinCodeStatus::Used,
                    'used_by' => $offset === 3 ? null : $owner->id,
                    'used_at' => $offset === 3 ? null : $now->copy()->subDays($offset + 1),
                ])->save();
            }
        }
    }

    private function downloads(User $member, User $vip): void
    {
        $files = Game::query()->with('files')->get()->pluck('files')->flatten()->take(6);

        foreach ($files as $index => $file) {
            Download::query()->create([
                'user_id' => $index % 2 === 0 ? $vip->id : $member->id,
                'game_file_id' => $file->id,
                'ip' => '127.0.0.'.$index,
                'day' => now()->subHours($index + 1)->toDateString(),
                'created_at' => now()->subHours($index + 1),
            ]);
        }
    }

    private function placeholders(): void
    {
        $disk = Storage::disk('public');

        $disk->put('placeholders/cover.svg', $this->svg(600, 800, '#9f1239', 'Kapak'));
        $disk->put('placeholders/banner.svg', $this->svg(1280, 420, '#1e1b4b', 'Banner'));
        $disk->put('placeholders/shot.svg', $this->svg(1280, 720, '#3f3f46', 'Ekran görüntüsü'));
    }

    private function svg(int $width, int $height, string $color, string $label): string
    {
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
            <rect width="100%" height="100%" fill="{$color}"/>
            <text x="50%" y="50%" fill="#ffffff" font-family="sans-serif" font-size="42" text-anchor="middle" dominant-baseline="middle">{$label}</text>
        </svg>
        SVG;
    }
}
