<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('games');
        config(['membership.games_disk' => 'games']);
        Storage::disk('games')->buildTemporaryUrlsUsing(
            fn (string $path, $expiration) => 'https://r2.test/'.rawurlencode($path).'?expires='.$expiration->getTimestamp()
        );
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $file = $this->file();

        $this->get(route('downloads.show', $file))->assertRedirect(route('login'));
        $this->assertSame(0, Download::query()->count());
    }

    public function test_a_member_gets_a_five_minute_link_for_a_free_game(): void
    {
        $file = $this->file();
        $user = User::factory()->create();

        $this->freezeTime();

        $this->actingAs($user)
            ->get(route('downloads.show', $file))
            ->assertRedirect(URL::temporarySignedRoute('downloads.signed', now()->addMinutes(5), [
                'file' => $file,
                'uye' => $user->id,
            ]));

        $this->assertSame(1, $file->game->fresh()->download_count);
        $this->assertDatabaseHas('downloads', [
            'user_id' => $user->id,
            'game_file_id' => $file->id,
        ]);
    }

    public function test_paid_files_need_an_unlock(): void
    {
        $file = $this->file(paid: true);

        $this->actingAs(User::factory()->create())
            ->get(route('downloads.show', $file))
            ->assertForbidden();

        $owner = User::factory()->create();
        $owner->unlocks()->create(['game_id' => $file->game_id, 'coins_paid' => 100]);

        $this->actingAs($owner)
            ->get(route('downloads.show', $file))
            ->assertRedirect();

        $this->assertSame(1, $file->game->fresh()->download_count);
    }

    public function test_the_daily_limit_stops_the_next_download(): void
    {
        config(['membership.download_limits.member' => 2]);
        $user = User::factory()->create();
        $file = $this->file();

        Download::factory()->create([
            'user_id' => $user->id,
            'game_file_id' => $this->file()->id,
            'created_at' => now(),
        ]);
        Download::factory()->create([
            'user_id' => $user->id,
            'game_file_id' => $this->file()->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('downloads.show', $file))
            ->assertStatus(429)
            ->assertSee('Günlük indirme sınırına ulaştın');

        $this->assertSame(0, $file->game->fresh()->download_count);
    }

    public function test_an_admin_can_download_a_vip_file_without_being_vip(): void
    {
        $file = $this->file(paid: true);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('downloads.show', $file))
            ->assertRedirect();

        $this->assertSame(1, $file->game->fresh()->download_count);
    }

    public function test_an_expired_signed_link_does_not_download(): void
    {
        config(['membership.games_disk' => 'local']);
        $file = $this->file();
        Storage::disk('local')->put($file->storage_path, 'yama');

        $member = User::factory()->create();
        $url = URL::temporarySignedRoute('downloads.signed', now()->subMinute(), [
            'file' => $file,
            'uye' => $member->id,
        ]);

        $this->get($url)->assertForbidden();

        $fresh = URL::temporarySignedRoute('downloads.signed', now()->addMinutes(5), [
            'file' => $file,
            'uye' => $member->id,
        ]);
        $this->get($fresh)->assertOk();
    }

    public function test_an_unpublished_file_and_a_vip_file_follow_the_policy(): void
    {
        config(['membership.games_disk' => 'local']);
        $hidden = $this->file();
        $hidden->game->forceFill(['is_published' => false])->save();
        Storage::disk('local')->put($hidden->storage_path, 'yama');

        $member = User::factory()->create();
        $this->get(URL::temporarySignedRoute('downloads.signed', now()->addMinute(), [
            'file' => $hidden,
            'uye' => $member->id,
        ]))->assertForbidden();

        $vipFile = $this->file(paid: true);
        Storage::disk('local')->put($vipFile->storage_path, 'yama');
        $url = URL::temporarySignedRoute('downloads.signed', now()->addMinute(), [
            'file' => $vipFile,
            'uye' => $member->id,
        ]);

        $this->get($url)->assertForbidden();

        $owner = User::factory()->create();
        $owner->unlocks()->create(['game_id' => $vipFile->game_id, 'coins_paid' => 100]);
        $this->get(URL::temporarySignedRoute('downloads.signed', now()->addMinute(), [
            'file' => $vipFile,
            'uye' => $owner->id,
        ]))->assertOk();
    }

    private function file(bool $paid = false): GameFile
    {
        $game = Game::factory()->create([
            'coin_price' => $paid ? 100 : 0,
            'download_count' => 0,
        ]);

        return GameFile::factory()->create([
            'game_id' => $game->id,
            'storage_path' => 'oyunlar/'.$game->slug.'/windows.zip',
        ]);
    }
}
