<?php

namespace Database\Seeders;

use App\Enums\GameAccess;
use App\Enums\Platform;
use App\Enums\TranslationStatus;
use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->placeholders();

        User::factory()->admin()->create([
            'name' => 'Yönetici',
            'email' => 'admin@ruhscans.test',
        ]);

        User::factory()->create([
            'name' => 'Deneme Üye',
            'email' => 'uye@ruhscans.test',
        ]);

        User::factory()->vip()->create([
            'name' => 'VIP Üye',
            'email' => 'vip@ruhscans.test',
        ]);

        $tags = collect([
            'Romantik', 'Korku', 'Fantastik', 'Bilim Kurgu',
            'Komedi', 'Dram', 'Gizem', 'Macera',
        ])->map(fn (string $name) => Tag::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        $titles = [
            'Gümüş Bahçe', 'Gece Vapuru', 'Kırık Ayna', 'Yıldız Çarşısı',
            'Sessiz Koridor', 'Amber Mektup', 'Son Peron', 'Mavi Çatı',
            'Kayıp Defter', 'İnce Yağmur', 'Eski Anahtar', 'Puslu Liman',
        ];

        foreach ($titles as $index => $title) {
            $game = Game::factory()->create([
                'title' => $title,
                'slug' => Str::slug($title),
                'access' => $index < 6 ? GameAccess::Free : GameAccess::Vip,
                'translation_status' => $index % 3 === 0 ? TranslationStatus::Complete : TranslationStatus::Ongoing,
                'translation_percent' => $index % 3 === 0 ? 100 : (($index + 1) * 7) % 100,
            ]);

            $game->tags()->attach($tags->random(2)->pluck('id'));

            $game->files()->create([
                'label' => 'Windows',
                'platform' => Platform::Windows,
                'storage_path' => 'games/'.$game->slug.'-windows.zip',
                'size_bytes' => $game->size_mb * 1024 * 1024,
                'version' => $game->game_version,
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
