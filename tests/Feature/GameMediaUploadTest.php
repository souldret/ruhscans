<?php

namespace Tests\Feature;

use App\Filament\Resources\GameResource\Pages\CreateGame;
use App\Models\Game;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GameMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function video(string $name, string $brand): UploadedFile
    {
        // fileinfo'nun gerçek video olarak tanıdığı en küçük ftyp kutusu.
        $box = pack('N', 24).'ftyp'.$brand.pack('N', 0).$brand.'isom';

        return UploadedFile::fake()->createWithContent($name, $box.str_repeat("\0", 2048));
    }

    public function test_cover_banner_and_mixed_media_upload_without_errors(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateGame::class)
            ->fillForm([
                'title' => 'Medya Yükleme',
                'slug' => 'medya-yukleme',
                'translation_status' => 'ongoing',
                'translation_percent' => 50,
                'size_mb' => 100,
                'coin_price' => 0,
                'cover_path' => UploadedFile::fake()->image('kapak.png', 1200, 1600),
                'banner_path' => UploadedFile::fake()->image('banner.webp', 1920, 600),
                'screenshots' => [
                    UploadedFile::fake()->image('kare.jpg', 1280, 720),
                    UploadedFile::fake()->image('hareket.gif', 320, 240),
                    $this->video('klip.mp4', 'mp42'),
                    $this->video('telefon.mov', 'qt  '),
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $game = Game::query()->where('slug', 'medya-yukleme')->firstOrFail();

        $this->assertNotNull($game->cover_path);
        $this->assertNotNull($game->banner_path);
        Storage::disk('public')->assertExists($game->cover_path);
        Storage::disk('public')->assertExists($game->banner_path);
        $this->assertCount(4, $game->screenshots);

        foreach ($game->screenshots as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_svg_is_rejected_as_cover(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create();

        $svg = UploadedFile::fake()->createWithContent('kapak.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        Livewire::actingAs($admin)
            ->test(CreateGame::class)
            ->fillForm([
                'title' => 'Kötü Kapak',
                'slug' => 'kotu-kapak',
                'translation_status' => 'ongoing',
                'translation_percent' => 0,
                'size_mb' => 1,
                'coin_price' => 0,
                'cover_path' => $svg,
            ])
            ->call('create')
            ->assertHasFormErrors(['cover_path']);
    }
}
