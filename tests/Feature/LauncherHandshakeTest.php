<?php

namespace Tests\Feature;

use App\Mail\SuspiciousLoginMail;
use App\Enums\CoinTransactionType;
use App\Models\Game;
use App\Models\LauncherDevice;
use App\Models\User;
use App\Services\CoinService;
use App\Services\GameUnlocker;
use App\Services\Launcher\LauncherSigner;
use App\Support\SecretRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LauncherHandshakeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{key_id: string, private_b64: string, public_b64: string} */
    private array $keys;

    protected function setUp(): void
    {
        parent::setUp();

        $this->keys = LauncherSigner::generate('k1');
 \Illuminate\Support\Facades\Config::set([
 'launcher.key_id' => $this->keys['key_id'],
 'launcher.private_key' => $this->keys['private_b64'],
 'launcher.public_keys' => $this->keys['key_id'].':'.$this->keys['public_b64'],
            'launcher.device_limit' => 3,
            'launcher.device_window_days' => 30,
            'launcher.reinstall_grace_minutes' => 30,
            'launcher.login.delay_step_ms' => 0,
            'launcher.login.max_delay_ms' => 0,
            'launcher.login.lock_after' => 8,
            'launcher.login.alert_after' => 5,
            'launcher.login.ip_per_minute' => 30,
            'launcher.login.account_per_minute' => 20,
        ]);
    }

    public function test_valid_token_gets_a_byte_exact_signed_payload(): void
 {
 $user = User::factory()->create();
 $game = Game::factory()->create(['launcher_enabled' => true, 'launcher_min_version' => '1.0.0']);
        Sanctum::actingAs($user);

        $nonce = 'nonce-abc-12345678';
        $response = $this->postJson('/api/v1/launcher/handshake', $this->body($game, $nonce))
            ->assertOk()
            ->assertJsonStructure(['payload', 'signature', 'key_id']);

        $payload = $response->json('payload');
        $signature = $response->json('signature');

        $this->assertIsString($payload);
        $this->assertTrue(app(LauncherSigner::class)->verify($payload, $signature, 'k1'));

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($decoded['allowed']);
        $this->assertSame($nonce, $decoded['client_nonce']);
        $this->assertSame($user->id, $decoded['user_id']);
        $this->assertSame($game->slug, $decoded['game_slug']);
        $this->assertSame('dev-1', $decoded['device_id']);
 $this->assertArrayHasKey('update_notice', $decoded);
 $this->assertNull($decoded['update_notice']);

 $again = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertSame($payload, $again);
    }

    public function test_update_notice_appears_after_the_game_version_changes(): void
 {
 $user = User::factory()->create();
 $game = Game::factory()->create([
 'launcher_enabled' => true,
 'game_version' => '1.0',
 'translation_version' => '1.0',
 'coin_price' => 10,
 ]);
 app(CoinService::class)->credit($user, 50, CoinTransactionType::Bonus);
 app(GameUnlocker::class)->unlock($user, $game);
 Sanctum::actingAs($user);

 $fresh = json_decode(
 $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-update-000001'))->assertOk()->json('payload'),
 true,
 512,
 JSON_THROW_ON_ERROR,
 );
 $this->assertNull($fresh['update_notice']);

 $game->update(['game_version' => '2.0']);

 $changed = json_decode(
 $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-update-000002'))->assertOk()->json('payload'),
 true,
 512,
 JSON_THROW_ON_ERROR,
 );
 $this->assertSame('2.0', $changed['update_notice']['game_version']);
 $this->assertSame('1.0', $changed['update_notice']['seen_game_version']);
 }

 public function test_invalid_token_is_rejected(): void
    {
        $game = Game::factory()->create(['launcher_enabled' => true]);

        $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-abc-12345678'), [
            'Authorization' => 'Bearer not-a-real-token',
        ])->assertUnauthorized();
    }

    public function test_replayed_nonce_is_data_the_client_can_reject(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['launcher_enabled' => true]);
        Sanctum::actingAs($user);

        $nonce = 'nonce-replay-000001';
        $first = $this->postJson('/api/v1/launcher/handshake', $this->body($game, $nonce))->assertOk();
        $second = $this->postJson('/api/v1/launcher/handshake', $this->body($game, $nonce))->assertOk();

        $a = json_decode($first->json('payload'), true, 512, JSON_THROW_ON_ERROR);
        $b = json_decode($second->json('payload'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($nonce, $a['client_nonce']);
        $this->assertSame($nonce, $b['client_nonce']);
        $this->assertNotSame($a['server_time'], $b['server_time']);
        $this->assertNotSame($first->json('signature'), $second->json('signature'));
    }

    public function test_device_limit_blocks_with_a_turkish_message_and_removable_ids(): void
    {
        config(['launcher.device_limit' => 1, 'launcher.reinstall_grace_minutes' => 30]);

        $user = User::factory()->create();
        $game = Game::factory()->create(['launcher_enabled' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-first-0000001', 'device-old'))
            ->assertOk();

        $denied = $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-second-000002', 'device-new'))
            ->assertOk();

        $payload = json_decode($denied->json('payload'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['allowed']);
        $this->assertSame('device_limit', $payload['reason']);
        $this->assertStringContainsString('cihaz limiti', $payload['message']);
        $this->assertContains('device-old', $payload['removable_device_ids']);
        $this->assertTrue(app(LauncherSigner::class)->verify($denied->json('payload'), $denied->json('signature')));
    }

    public function test_user_can_remove_a_device_and_a_new_one_fits(): void
    {
        config(['launcher.device_limit' => 1]);

        $user = User::factory()->create();
        $game = Game::factory()->create(['launcher_enabled' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-first-0000001', 'device-old'))->assertOk();

        $device = LauncherDevice::query()->where('device_id', 'device-old')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('account.devices.destroy', $device))
            ->assertRedirect();

        $this->assertDatabaseMissing('launcher_devices', ['id' => $device->id]);

        $allowed = $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-third-0000003', 'device-new'))
            ->assertOk();

        $payload = json_decode($allowed->json('payload'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($payload['allowed']);
    }

    public function test_banned_user_is_denied(): void
    {
        $user = User::factory()->create();
 $game = Game::factory()->create(['launcher_enabled' => true]);
 $user->delete();

 Sanctum::actingAs(User::withTrashed()->findOrFail($user->id));

        $response = $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-banned-000001'))
            ->assertOk();

        $payload = json_decode($response->json('payload'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['allowed']);
        $this->assertSame('banned', $payload['reason']);
        $this->assertStringContainsString('kapatıldı', $payload['message']);
    }

    public function test_kill_switch_points_at_a_new_version(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create([
            'launcher_enabled' => false,
            'launcher_message' => 'Modül kapatıldı. Yeni sürümü indir: ruhscans.example/guncelle',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-kill-00000001'))
            ->assertOk();

        $payload = json_decode($response->json('payload'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($payload['allowed']);
        $this->assertSame('kill_switch', $payload['reason']);
        $this->assertStringContainsString('Yeni sürümü indir', $payload['message']);
    }

    public function test_old_client_login_and_verify_still_work(): void
    {
        $user = User::factory()->create([
            'email' => 'eski@site.test',
            'password' => 'gizli-parola',
        ]);

        $login = $this->postJson('/api/v1/login', [
            'email' => 'eski@site.test',
            'password' => 'gizli-parola',
        ])->assertOk();

        $body = $login->json();
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('vip', $body);
        $this->assertArrayHasKey('vip_until', $body);
        $this->assertFalse($body['vip']);

        $this->postJson('/api/v1/verify', [], [
            'Authorization' => 'Bearer '.$body['token'],
        ])
            ->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('vip', false)
            ->assertJsonPath('vip_until', null);
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'ban@site.test',
            'password' => 'gizli-parola',
        ]);
        $user->delete();

        $this->postJson('/api/v1/login', [
            'email' => 'ban@site.test',
            'password' => 'gizli-parola',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_brute_force_delay_and_lock_and_mail(): void
    {
        Mail::fake();
        \Illuminate\Support\Facades\Config::set([
            'launcher.login.delay_step_ms' => 50,
            'launcher.login.max_delay_ms' => 200,
            'launcher.login.lock_after' => 3,
            'launcher.login.alert_after' => 2,
            'launcher.login.account_per_minute' => 20,
            'launcher.login.ip_per_minute' => 30,
        ]);

        User::factory()->create([
            'email' => 'kaba@site.test',
            'password' => 'dogru-parola',
        ]);

        $started = microtime(true);
        $this->postJson('/api/v1/login', [
            'email' => 'kaba@site.test',
            'password' => 'yanlis-1',
        ])->assertUnprocessable();
        $this->postJson('/api/v1/login', [
            'email' => 'kaba@site.test',
            'password' => 'yanlis-2',
        ])->assertUnprocessable();
        $elapsed = microtime(true) - $started;

        $this->assertGreaterThan(0.04, $elapsed);
        Mail::assertSent(SuspiciousLoginMail::class);

        $locked = $this->postJson('/api/v1/login', [
            'email' => 'kaba@site.test',
            'password' => 'yanlis-3',
        ])->assertUnprocessable();

        $this->assertStringContainsString('geçici olarak kilitli', (string) $locked->json('errors.email.0'));
    }

    public function test_logs_mask_password_and_token(): void
    {
        $redacted = SecretRedactor::redact([
            'email' => 'a@b.c',
            'password' => 'gizli',
            'token' => 'plain-token',
            'nested' => ['authorization' => 'Bearer abc'],
        ]);

        $this->assertSame('[gizli]', $redacted['password']);
        $this->assertSame('[gizli]', $redacted['token']);
        $this->assertSame('[gizli]', $redacted['nested']['authorization']);
        $this->assertSame('a@b.c', $redacted['email']);
    }

    public function test_key_rotation_accepts_a_previous_public_key(): void
    {
        $old = LauncherSigner::generate('k0');
        $current = $this->keys;

        \Illuminate\Support\Facades\Config::set([
 'launcher.key_id' => $current['key_id'],
            'launcher.private_key' => $current['private_b64'],
            'launcher.public_keys' => 'k0:'.$old['public_b64'].',k1:'.$current['public_b64'],
        ]);

        $user = User::factory()->create();
        $game = Game::factory()->create(['launcher_enabled' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/launcher/handshake', $this->body($game, 'nonce-rotate-00001'))
            ->assertOk();

        $signer = app(LauncherSigner::class);
        $this->assertTrue($signer->verify($response->json('payload'), $response->json('signature'), 'k1'));
        $this->assertArrayHasKey('k0', $signer->publicKeys());
        $this->assertArrayHasKey('k1', $signer->publicKeys());
    }

    /**
     * @return array<string, string>
     */
    private function body(Game $game, string $nonce, string $device = 'dev-1'): array
    {
        return [
            'game_slug' => $game->slug,
            'game_version' => '1.0.0',
            'launcher_version' => '1.4.0',
            'device_id' => $device,
            'client_nonce' => $nonce,
        ];
    }
}
