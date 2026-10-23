<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_before_downloading(): void
    {
        $game = Game::factory()->create();

        $this->get(route('games.download', $game))
            ->assertRedirect(route('login'));
    }

    public function test_member_download_is_recorded_and_streamed(): void
    {
        Storage::fake('games');

        $game = Game::factory()->create(['archive_path' => 'games/ornek.zip']);
        Storage::disk('games')->put($game->archive_path, 'arsiv-icerigi');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('games.download', $game))
            ->assertOk()
            ->assertDownload($game->slug.'.zip');

        $this->assertDatabaseHas('downloads', [
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }

    public function test_missing_archive_returns_a_turkish_message(): void
    {
        Storage::fake('games');

        $game = Game::factory()->create(['archive_path' => 'games/yok.zip']);

        $this->actingAs(User::factory()->create())
            ->from(route('games.show', $game))
            ->get(route('games.download', $game))
            ->assertRedirect(route('games.show', $game))
            ->assertSessionHas('error', 'Oyun dosyası şu anda indirilemiyor. Lütfen daha sonra tekrar deneyin.');

        $this->assertDatabaseCount('downloads', 0);
    }

    public function test_draft_cannot_be_downloaded(): void
    {
        Storage::fake('games');

        $game = Game::factory()->draft()->create(['archive_path' => 'games/taslak.zip']);
        Storage::disk('games')->put($game->archive_path, 'taslak');

        $this->actingAs(User::factory()->create())
            ->get(route('games.download', $game))
            ->assertForbidden();
    }
}
