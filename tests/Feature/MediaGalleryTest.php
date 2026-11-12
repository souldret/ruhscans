<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_slider_shows_one_slide_and_game_page_plays_gif_and_video(): void
    {
        Game::factory()->count(2)->create(['is_published' => true, 'published_at' => now()->subDay()]);

        $game = Game::factory()->create([
            'title' => 'Medya Oyunu',
            'is_published' => true,
            'published_at' => now(),
            'screenshots' => [
                'games/screenshots/kare.jpg',
                'games/screenshots/hareket.gif',
                'games/screenshots/klip.mp4',
            ],
        ]);

        $home = $this->get(route('home'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($home, 'data-slide'));
        $this->assertSame(1, substr_count($home, 'aria-hidden="false"'));
        $this->assertStringContainsString('data-hero', $home);
        $this->assertStringNotContainsString('heroSlider', $home);

        $page = $this->get(route('games.show', $game))->assertOk()->getContent();

        $this->assertStringContainsString('hareket.gif', $page);
        $this->assertStringContainsString('<video src="', $page);
        $this->assertStringContainsString('klip.mp4', $page);
        $this->assertStringContainsString('data-shot-video', $page);
        $this->assertStringContainsString('"kind":"video"', $page);
        $this->assertStringContainsString('data-censored="1"', $page);
        $this->assertStringContainsString('data-reveal-shot', $page);
        $this->assertStringContainsString('scale-110 blur-xl', $page);
        $this->assertStringNotContainsString('klip.mp4" muted playsinline preload="metadata" data-shot-media class="pointer-events-none h-36 w-64 bg-black object-cover sm:h-44 sm:w-80 scale-110 blur-xl"', $page);
    }

    public function test_screenshot_blur_can_be_turned_off_per_game(): void
    {
        $game = Game::factory()->create([
            'is_published' => true,
            'published_at' => now(),
            'blur_screenshots' => false,
            'screenshots' => ['games/screenshots/kare.jpg'],
        ]);

        $page = $this->get(route('games.show', $game))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-censored', $page);
        $this->assertStringNotContainsString('data-reveal-shot', $page);
        $this->assertStringNotContainsString('scale-110 blur-xl', $page);
        $this->assertStringContainsString('kare.jpg', $page);
    }
}
