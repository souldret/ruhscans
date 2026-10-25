<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Jobs\MakeCoverVariants;
use App\Models\Game;
use App\Services\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerformanceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_is_served_from_cache_until_a_game_changes(): void
    {
        $game = Game::factory()->create(['title' => 'İlk Oyun']);

        $this->get(route('home'))->assertOk()->assertSee('İlk Oyun');
        $this->assertTrue(Cache::has('catalog.home'));

        Game::query()->whereKey($game->id)->update(['title' => 'Yeni Ad']);
        CatalogCache::flush();
        $this->assertFalse(Cache::has('catalog.home'));

        $this->get(route('home'))->assertOk()->assertSee('Yeni Ad');
    }

    public function test_a_cover_change_queues_resized_variants_and_the_card_uses_them(): void
    {
        Queue::fake();
        Storage::fake('public');

        $game = Game::factory()->create(['cover_path' => null]);
        $game->update(['cover_path' => 'games/covers/ornek.jpg']);

        Queue::assertPushed(MakeCoverVariants::class);

        Storage::disk('public')->put('games/covers/ornek-240.webp', 'kucuk');
        Storage::disk('public')->put('games/covers/ornek-480.webp', 'buyuk');

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('ornek-240.webp', $html);
        $this->assertStringContainsString('srcset=', $html);
    }

    public function test_static_assets_are_cached_for_a_year(): void
    {
        $request = Request::create('/build/manifest.json', 'GET');
        $response = (new SecurityHeaders)->handle(
            $request,
            fn () => response('{}', 200)
        );

        $header = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('max-age=31536000', $header);
        $this->assertStringContainsString('immutable', $header);
    }

    public function test_the_javascript_bundle_stays_under_one_hundred_kilobytes_gzipped(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $file = public_path('build/'.$manifest['resources/js/app.js']['file']);
        $gzipped = strlen(gzencode((string) file_get_contents($file), 9));

        $this->assertLessThan(100 * 1024, $gzipped);
    }

    public function test_the_catalog_cache_can_be_flushed(): void
    {
        Cache::put('catalog.home', 'eski', 60);
        Cache::put('catalog.tags', 'eski', 60);

        CatalogCache::flush();

        $this->assertFalse(Cache::has('catalog.home'));
        $this->assertFalse(Cache::has('catalog.tags'));
    }
}
