<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Filament\Resources\GameResource\Pages\ListGames;
use App\Models\Download;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\GameReview;
use App\Models\GameUnlock;
use App\Models\Tag;
use App\Models\User;
use App\Services\CoinService;
use App\Services\GameUnlocker;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_favorites_and_update_notice(): void
    {
        $game = Game::factory()->create([
            'title' => 'Gumus Bahce',
            'game_version' => '1.0',
            'translation_version' => '1.0',
            'coin_price' => 40,
        ]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 100, CoinTransactionType::Bonus);
        app(GameUnlocker::class)->unlock($user, $game);

        $this->actingAs($user)
            ->post(route('games.favorite', $game))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Kütüphanem')
            ->assertSee('Gumus Bahce')
            ->assertSee('Favoriler');

        $game->update(['game_version' => '1.1']);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Yeni sürüm');

        $this->actingAs($user)
            ->post(route('account.acknowledge', $game))
            ->assertRedirect();

        $unlock = GameUnlock::query()->where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();
        $this->assertSame('1.1', $unlock->seen_game_version);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertDontSee('Yeni sürüm');

        $this->actingAs($user)
            ->post(route('games.favorite', $game))
            ->assertRedirect();

        $this->assertDatabaseMissing('game_favorites', [
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }

    public function test_spoiler_hides_the_body_and_helpful_vote_toggles(): void
    {
        $game = Game::factory()->create();
        $author = User::factory()->create();
        $reader = User::factory()->create();
        $review = GameReview::factory()->create([
            'game_id' => $game->id,
            'user_id' => $author->id,
            'body' => 'Gizli son aciklama',
            'spoiler' => true,
        ]);

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Spoiler — göstermek için tıkla')
            ->assertSee('Gizli son aciklama', false);

        $this->actingAs($author)
            ->post(route('games.reviews.vote', $review))
            ->assertRedirect()
            ->assertSessionHas('review', 'Kendi yorumunu oylayamazsın.');

        $this->actingAs($reader)
            ->post(route('games.reviews.vote', $review))
            ->assertRedirect();

        $this->assertDatabaseHas('game_review_votes', [
            'game_review_id' => $review->id,
            'user_id' => $reader->id,
        ]);

        $this->actingAs($reader)
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Faydalı (1)');

        $this->actingAs($reader)
            ->post(route('games.reviews.vote', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('game_review_votes', [
            'game_review_id' => $review->id,
            'user_id' => $reader->id,
        ]);
    }

    public function test_owner_refunds_once_inside_the_window_and_not_after_a_download(): void
    {
        $game = Game::factory()->create(['coin_price' => 50]);
        $file = GameFile::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 100, CoinTransactionType::Bonus);
        $unlock = app(GameUnlocker::class)->unlock($user, $game);

        $this->actingAs($user)
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('Yanlış oyunsa iade et');

        $this->actingAs($user)
            ->post(route('games.refund', $game))
            ->assertRedirect();

        $this->assertSame(100, $user->fresh()->coin_balance);
        $this->assertDatabaseMissing('game_unlocks', ['id' => $unlock->id]);

        $again = app(GameUnlocker::class)->unlock($user, $game);
        Download::factory()->create([
            'user_id' => $user->id,
            'game_file_id' => $file->id,
        ]);

        $this->actingAs($user)
            ->from(route('games.show', $game))
            ->post(route('games.refund', $game))
            ->assertRedirect(route('games.show', $game))
            ->assertSessionHas('refund_error');

        $this->assertDatabaseHas('game_unlocks', ['id' => $again->id]);

        $late = User::factory()->create();
        app(CoinService::class)->credit($late, 100, CoinTransactionType::Bonus);
        $lateUnlock = app(GameUnlocker::class)->unlock($late, $game);
        $this->travel(31)->minutes();

        $this->actingAs($late)
            ->post(route('games.refund', $game))
            ->assertSessionHas('refund_error');

        $this->assertDatabaseHas('game_unlocks', ['id' => $lateUnlock->id]);
    }

    public function test_related_games_fall_back_to_the_same_price_band(): void
    {
        $game = Game::factory()->create(['coin_price' => 100, 'title' => 'Ana Oyun']);
        $near = Game::factory()->create(['coin_price' => 120, 'title' => 'Yakin Fiyat']);
 $far = Game::factory()->create(['coin_price' => 900, 'title' => 'Uzak Fiyat']);

 $ids = $this->get(route('games.show', $game))
 ->assertOk()
 ->assertSee('Yakin Fiyat')
 ->viewData('related')
 ->pluck('id')
 ->all();

 $this->assertSame([$near->id], array_values(array_intersect($ids, [$near->id])));
 $this->assertNotContains($far->id, $ids);

        $this->assertNotSame($game->id, $near->id);
    }

    public function test_unpublished_preview_is_signed_and_admin_only(): void
    {
        $game = Game::factory()->create(['is_published' => false, 'published_at' => null]);
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $url = URL::temporarySignedRoute('games.show', now()->addHour(), [
            'game' => $game,
            'onizle' => 1,
        ]);

        $this->get(route('games.show', $game))->assertNotFound();
        $this->actingAs($member)->get($url)->assertNotFound();
        $this->actingAs($admin)->get($url)->assertOk()->assertSee($game->title);
    }

    public function test_bulk_tag_and_sale_apply_to_selected_games(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create(['name' => 'Okul']);
        $first = Game::factory()->create();
        $second = Game::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListGames::class)
            ->callTableBulkAction('attachTags', [$first, $second], data: [
                'tags' => [$tag->id],
            ])
            ->assertHasNoTableBulkActionErrors();

        $this->assertTrue($first->fresh()->tags->contains($tag));
        $this->assertTrue($second->fresh()->tags->contains($tag));

        $ends = now()->addDays(3)->startOfMinute();

        Livewire::actingAs($admin)
            ->test(ListGames::class)
            ->callTableBulkAction('setSale', [$first], data: [
                'sale_price' => 15,
                'sale_ends_at' => $ends->toDateTimeString(),
            ])
            ->assertHasNoTableBulkActionErrors();

        $saved = $first->fresh();
        $this->assertSame(15, $saved->sale_price);
        $this->assertNotNull($saved->sale_ends_at);
    }

    public function test_cover_srcset_lists_the_960_variant(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('covers/poster.jpg', 'x');
        Storage::disk('public')->put('covers/poster-240.webp', 'x');
        Storage::disk('public')->put('covers/poster-480.webp', 'x');
        Storage::disk('public')->put('covers/poster-960.webp', 'x');

        $game = Game::factory()->create(['cover_path' => 'covers/poster.jpg']);

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('poster-960.webp', false)
            ->assertSee('srcset=', false);
    }
}
