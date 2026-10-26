<?php

namespace Tests\Feature;

use App\Models\CodeAttempt;
use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use App\Models\Download;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\GameReview;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CodeAttemptPolicy;
use App\Policies\CoinCodeBatchPolicy;
use App\Policies\CoinCodePolicy;
use App\Policies\DownloadPolicy;
use App\Policies\GameFilePolicy;
use App\Policies\GamePolicy;
use App\Policies\GameReviewPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_policies_deny_another_members_records(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $download = Download::factory()->create(['user_id' => $other->id]);
        $this->assertFalse((new DownloadPolicy)->view($member, $download));
        $this->assertTrue((new DownloadPolicy)->view($admin, $download));

        $code = CoinCode::factory()->create(['used_by' => $other->id]);
        $this->assertFalse((new CoinCodePolicy)->view($member, $code));
        $this->assertTrue((new CoinCodePolicy)->view($admin, $code));

        $attempt = CodeAttempt::factory()->create(['user_id' => $other->id]);
        $this->assertFalse((new CodeAttemptPolicy)->view($member, $attempt));
        $this->assertTrue((new CodeAttemptPolicy)->view($admin, $attempt));

        $batch = CoinCodeBatch::factory()->create();
        $this->assertFalse((new CoinCodeBatchPolicy)->view($member, $batch));
        $this->assertTrue((new CoinCodeBatchPolicy)->view($admin, $batch));

        $this->assertFalse((new UserPolicy)->view($member, $other));
        $this->assertTrue((new UserPolicy)->view($admin, $other));
        $this->assertFalse((new UserPolicy)->delete($admin, $admin));

        $tag = Tag::factory()->create();
        $this->assertFalse((new TagPolicy)->update($member, $tag));
        $this->assertTrue((new TagPolicy)->update($admin, $tag));

        $game = Game::factory()->create();
        $this->assertTrue((new GamePolicy)->view($member, $game));
        $this->assertFalse((new GamePolicy)->update($member, $game));
        $this->assertTrue((new GamePolicy)->update($admin, $game));

        $review = GameReview::factory()->create(['user_id' => $other->id, 'game_id' => $game->id]);
        $this->assertFalse((new GameReviewPolicy)->delete($member, $review));
        $this->assertTrue((new GameReviewPolicy)->delete($other, $review));
        $this->assertTrue((new GameReviewPolicy)->delete($admin, $review));

        $file = GameFile::factory()->create(['game_id' => $game->id]);
        $this->assertTrue((new GameFilePolicy)->download($member, $file));
        $game->forceFill(['coin_price' => 100])->save();
        $file->unsetRelation('game');
        $this->assertFalse((new GameFilePolicy)->download($member, $file->fresh()));
        $this->assertTrue((new GameFilePolicy)->download($admin, $file->fresh()));
    }

    public function test_a_member_cannot_export_another_batch_or_delete_another_review(): void
    {
        $member = User::factory()->create();
        $batch = CoinCodeBatch::factory()->create();

        $this->actingAs($member)
            ->get('/yonetim/coin-code-batches')
            ->assertForbidden();

        $review = GameReview::factory()->create();

        $this->actingAs($member)
            ->delete(route('games.reviews.destroy', $review))
            ->assertForbidden();
        $this->assertModelExists($review);
    }

    public function test_a_signed_link_dies_when_vip_ends_and_rejects_a_foreign_user(): void
    {
        Storage::fake('local');
        config(['membership.games_disk' => 'local']);

        $game = Game::factory()->create(['coin_price' => 100]);
        $file = GameFile::factory()->create([
            'game_id' => $game->id,
            'storage_path' => 'oyunlar/'.$game->slug.'/windows.zip',
        ]);
        Storage::disk('local')->put($file->storage_path, 'yama');

        $member = User::factory()->create();
        $url = URL::temporarySignedRoute('downloads.signed', now()->addMinutes(5), [
            'file' => $file,
            'uye' => $member->id,
            'ip' => hash('sha256', '127.0.0.1'),
        ]);

        $this->get($url)->assertForbidden();

        $stranger = User::factory()->create();
        $stolen = URL::temporarySignedRoute('downloads.signed', now()->addMinutes(5), [
            'file' => $file,
            'uye' => $stranger->id,
            'ip' => hash('sha256', '127.0.0.1'),
        ]);
        $this->get($stolen)->assertForbidden();

        $this->get(route('downloads.signed', $file))->assertForbidden();
    }

    public function test_profile_update_cannot_assign_role_or_vip(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->patch(route('profile.update'), [
            'name' => 'Yeni Ad',
            'email' => $member->email,
            'role' => 'admin',
            'coin_balance' => 999999,
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('Yeni Ad', $member->name);
        $this->assertFalse($member->isAdmin());
        $this->assertSame(0, $member->coin_balance);
    }

    public function test_password_reset_does_not_reveal_whether_the_email_exists(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'uye@ruhscans.test']);

        $known = $this->post(route('password.email'), ['email' => 'uye@ruhscans.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'yok@ruhscans.test']);

        $known->assertSessionHas('status', 'E-posta kayıtlıysa sıfırlama bağlantısı gönderildi.');
        $unknown->assertSessionHas('status', 'E-posta kayıtlıysa sıfırlama bağlantısı gönderildi.');
        $known->assertSessionMissing('errors');
        $unknown->assertSessionMissing('errors');
    }

    public function test_passwords_hash_with_the_configured_driver(): void
    {
        config(['hashing.driver' => 'argon2id', 'hashing.rehash_on_login' => false]);
        $user = User::factory()->create(['password' => 'gizli-parola']);

        $this->assertStringStartsWith('$argon2id$', $user->password);
        $this->assertTrue(Hash::check('gizli-parola', $user->password));
    }

    public function test_session_cookie_is_http_only_and_login_rotates_the_session(): void
    {
        config(['session.secure' => false]);
        $user = User::factory()->create();

        $login = $this->get(route('login'));
        $before = $login->getCookie('ruhscans-session', false)?->getValue()
            ?? session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($user);

        $cookie = $response->getCookie(config('session.cookie'), false);
        if ($cookie !== null) {
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertSame('lax', $cookie->getSameSite());
        }
    }

    public function test_login_search_download_and_redeem_are_rate_limited(): void
    {
        $this->post(route('login.store'), ['email' => 'yok@ruhscans.test', 'password' => 'yanlis'])
            ->assertStatus(302);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login.store'), ['email' => 'yok@ruhscans.test', 'password' => 'yanlis']);
        }
        $this->post(route('login.store'), ['email' => 'yok@ruhscans.test', 'password' => 'yanlis'])
            ->assertStatus(429);

        for ($i = 0; $i < 61; $i++) {
            $this->get(route('games.index', ['q' => 'a']));
        }
        $this->get(route('games.index', ['q' => 'a']))->assertStatus(429);
    }

    public function test_a_script_in_the_game_description_is_escaped(): void
    {
        $game = Game::factory()->create([
            'description' => '<script>alert(1)</script>',
            'title' => 'Temiz Oyun',
        ]);

        $html = $this->get(route('games.show', $game))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $home = $this->get(route('home'));
        $policy = (string) $home->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self'", $policy);
        $this->assertStringNotContainsString('unsafe-eval', $policy);
    }

    public function test_the_admin_panel_is_not_at_the_default_path_and_members_are_rejected(): void
    {
        $this->get('/admin')->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get('/yonetim')
            ->assertForbidden();
    }
}
