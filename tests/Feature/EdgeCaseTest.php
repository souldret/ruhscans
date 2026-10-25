<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('games');
        config(['membership.games_disk' => 'games']);
        Storage::disk('games')->buildTemporaryUrlsUsing(
            fn (string $path, $expiration) => 'https://r2.test/'.rawurlencode($path)
        );
    }

    public function test_the_same_download_click_does_not_count_twice(): void
    {
        $user = User::factory()->create();
        $file = $this->file();

        $this->actingAs($user)->get(route('downloads.show', $file))->assertRedirect();
        $this->actingAs($user)->get(route('downloads.show', $file))->assertRedirect();

        $this->assertSame(1, Download::query()->count());
        $this->assertSame(1, $file->game->fresh()->download_count);
    }

    public function test_a_stored_utc_instant_displays_as_istanbul(): void
    {
        $this->travelTo('2026-07-01 00:30:00');
        $user = User::factory()->create(['created_at' => now()->addDay()]);

        $shown = $user->created_at->timezone(config('app.display_timezone'))->format('H:i');

        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('03:30', $shown);
    }

    public function test_catalog_rejects_long_negative_and_huge_page_input(): void
    {
        Game::factory()->create(['title' => 'Emoji 🎲', 'is_published' => true]);

        $this->get('/oyunlar?q='.str_repeat('a', 101))->assertSessionHasErrors('q');
        $this->get('/oyunlar?erisim=negatif')->assertSessionHasErrors('erisim');
        $this->get('/oyunlar?q=🎲&page=999999')->assertOk();
        $this->get('/oyunlar?q=')->assertOk();
    }

    public function test_deleting_a_user_keeps_the_row_and_hides_it(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertNull(User::query()->find($user->id));
    }

    private function file(): GameFile
    {
        $game = Game::factory()->create([
            'coin_price' => 0,
            'download_count' => 0,
        ]);

        return GameFile::factory()->create([
            'game_id' => $game->id,
            'storage_path' => 'oyunlar/'.$game->slug.'/windows.zip',
        ]);
    }
}
