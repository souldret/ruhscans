<?php

namespace Tests\Feature;

use App\Filament\Resources\GameResource\Pages\CreateGame;
use App\Models\Game;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_is_saved_unpublished_and_stays_off_the_site(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateGame::class)
            ->fillForm([
                'title' => 'Taslak Oyun',
                'slug' => 'taslak-oyun',
                'translation_status' => 'ongoing',
                'translation_percent' => 10,
                'size_mb' => 100,
                'coin_price' => 0,
                'is_published' => true,
            ])
            ->call('saveDraft')
            ->assertHasNoFormErrors();

        $game = Game::query()->where('slug', 'taslak-oyun')->first();

        $this->assertNotNull($game);
        $this->assertFalse($game->is_published);
        $this->assertNull($game->published_at);
        $this->assertSame($admin->id, $game->uploaded_by);

        $this->get(route('games.show', $game))->assertNotFound();
    }
}
