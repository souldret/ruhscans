<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameFile;
use App\Models\Tag;
use App\Models\User;
use App\Support\CounterBuffer;
use App\Support\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_game_returns_not_found(): void
    {
        $game = Game::factory()->create([
            'title' => 'Gizli Oyun',
            'is_published' => false,
        ]);

        $this->get(route('games.show', $game))->assertNotFound();
        $this->assertSame(0, $game->fresh()->view_count);
    }

    public function test_filters_stay_in_the_url_and_narrow_the_catalog(): void
    {
        $romance = Tag::factory()->create(['name' => 'Romantik']);
        $horror = Tag::factory()->create(['name' => 'Korku']);

        $match = Game::factory()->complete()->state(['coin_price' => 100])->create([
            'title' => 'Gümüş Bahçe',
            'developer' => 'Kuzey Stüdyo',
        ]);
        $match->tags()->attach($romance);

        $other = Game::factory()->create(['title' => 'Gece Vapuru']);
        $other->tags()->attach($horror);

        Game::factory()->create([
            'title' => 'Yayında Değil',
            'is_published' => false,
        ]);

        $this->get('/oyunlar?q=Gümüş&etiket[]='.$romance->slug.'&erisim=ucretli&ceviri=complete&sirala=yeni')
            ->assertOk()
            ->assertSee('Gümüş Bahçe')
            ->assertDontSee('Gece Vapuru')
            ->assertDontSee('Yayında Değil');
    }

    public function test_download_panel_changes_with_the_visitor(): void
    {
        $game = Game::factory()->create(['title' => 'Amber Mektup', 'coin_price' => 100]);
        GameFile::factory()->create(['game_id' => $game->id, 'label' => 'Ozel Dosya Adi']);

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Giriş yap')
            ->assertDontSee('Ozel Dosya Adi');

        $this->actingAs(User::factory()->create())
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('100 coin ile aç', false)
            ->assertDontSee('>Kod kullan<', false)
            ->assertDontSee('Ozel Dosya Adi');

        $owner = User::factory()->create();
        $owner->unlocks()->create(['game_id' => $game->id, 'coins_paid' => 100]);

        $ownerPage = $this->actingAs($owner)->get(route('games.show', $game))->assertOk();
        $this->assertStringContainsString('Ozel Dosya Adi', $ownerPage->getContent());
        $this->assertStringNotContainsString('100 coin ile aç', $ownerPage->getContent());

        $free = Game::factory()->create(['title' => 'Açık Bahçe']);
        GameFile::factory()->create(['game_id' => $free->id, 'label' => 'Ücretsiz paket']);

        $this->get(route('games.show', $free))
            ->assertOk()
            ->assertSee('Ücretsiz paket');
    }

    public function test_a_view_is_counted_once_for_the_same_visitor(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('games.show', $game))->assertOk();
        $this->actingAs($user)->get(route('games.show', $game))->assertOk();

        app(CounterBuffer::class)->flush('view_count', $game->id);

        $this->assertSame(1, $game->fresh()->view_count);
    }

    public function test_turkish_titles_become_ascii_slugs(): void
    {
        $this->assertSame('isigin-golgesinde', Slug::from('Işığın Gölgesinde'));

        $game = Game::factory()->create([
            'title' => 'Şekerçi Kızın Öyküsü',
            'slug' => 'gecici',
        ]);

        $this->assertSame('sekerci-kizin-oykusu', $game->slug);
        $this->get('/oyun/sekerci-kizin-oykusu')->assertOk();
    }

    public function test_a_long_title_and_a_game_without_art_still_render(): void
    {
        $bare = Game::factory()->create([
            'title' => 'Kapaksız Oyun',
            'cover_path' => null,
            'banner_path' => null,
            'screenshots' => [],
        ]);

        $long = Game::factory()->create([
            'title' => 'Gece Yarısı Kütüphanesinde Kaybolan Son Mektubun Uzun Adı',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($long->title, false);

        Game::query()->update(['coin_price' => 0]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Bu şeritte henüz oyun yok', false);

        $this->get(route('games.show', $bare))
            ->assertOk()
            ->assertSee('poster.svg', false)
            ->assertDontSee('Ekran görüntüleri', false);

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee($long->title, false);

        Game::query()->delete();

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee('Bu filtreyle eşleşen oyun yok', false);
    }
}
