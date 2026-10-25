<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_for_the_right_password(): void
    {
        $user = User::factory()->rich()->create([
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ])
            ->assertOk()
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('coin_balance', 1000)
            ->assertJsonPath('vip', false)
            ->assertJsonPath('vip_until', null)
            ->assertJsonStructure(['token', 'name', 'coin_balance', 'vip', 'vip_until']);
    }

    public function test_an_old_client_still_reads_token_vip_and_vip_until(): void
    {
        User::factory()->rich()->create([
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
            'vip_until' => now()->addDays(30),
        ]);

        $body = $this->postJson('/api/v1/login', [
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ])->assertOk()->json();

        $client = [
            'token' => $body['token'] ?? null,
            'vip' => $body['vip'] ?? null,
            'vip_until' => array_key_exists('vip_until', $body) ? $body['vip_until'] : 'missing',
        ];

        $this->assertIsString($client['token']);
        $this->assertNotSame('', $client['token']);
        $this->assertFalse($client['vip']);
        $this->assertNull($client['vip_until']);
        $this->assertSame(1000, $body['coin_balance']);
    }

    public function test_login_rejects_a_wrong_password_with_the_same_shape(): void
    {
        User::factory()->create([
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'uye@site.test',
            'password' => 'yanlis',
        ])
            ->assertStatus(422)
            ->assertJson(['message' => 'E-posta veya şifre hatalı.']);
    }

    public function test_verify_accepts_a_live_token_and_rejects_an_expired_one(): void
    {
        $user = User::factory()->rich()->create();
        $token = $user->createToken('oyun')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/verify')
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'coin_balance' => 1000,
                'vip' => false,
                'vip_until' => null,
            ]);

        config(['sanctum.expiration' => 1]);
        $this->travel(2)->minutes();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/verify')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Oturum geçersiz.']);
    }

    public function test_logout_removes_the_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/logout')->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_the_eleventh_login_in_a_minute_is_refused(): void
    {
        User::factory()->create([
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'uye@site.test',
                'password' => 'yanlis',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'uye@site.test',
            'password' => 'gizli-parola',
        ])
            ->assertStatus(429)
            ->assertJson(['message' => 'Çok fazla deneme. Bir dakika bekle.']);
    }

    public function test_logout_then_login_then_verify_keeps_one_token(): void
    {
        $user = User::factory()->rich()->create([
            'email' => 'vip@site.test',
            'password' => 'gizli-parola',
        ]);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'vip@site.test',
            'password' => 'gizli-parola',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/verify')
            ->assertUnauthorized();

        $fresh = $this->postJson('/api/v1/login', [
            'email' => 'vip@site.test',
            'password' => 'gizli-parola',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer '.$fresh)
            ->postJson('/api/v1/verify')
            ->assertOk()
            ->assertJsonPath('coin_balance', 1000);

        $this->assertNotSame($token, $fresh);
        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_the_splash_command_writes_the_site_name_and_api(): void
    {
        config(['app.name' => 'RuhScans', 'app.url' => 'https://oyun.test']);

        Artisan::call('renpy:splash');

        $script = file_get_contents(storage_path('app/renpy/zz_splash.rpy'));

        $this->assertStringContainsString('RT_SITE = "RuhScans"', $script);
        $this->assertStringContainsString('RT_API = "https://oyun.test/api/v1"', $script);
        $this->assertStringContainsString('tarafından Türkçeleştirilmiştir', $script);
        $this->assertStringContainsString('renpy.loadable("rt_logo.png")', $script);
        $this->assertStringContainsString('hasattr(renpy, "fetch")', $script);
        $this->assertStringContainsString('urllib.request', $script);
        $this->assertStringNotContainsString('label splashscreen', $script);
        $this->assertStringContainsString('rt_offline_days', $script);
        $this->assertStringContainsString('persistent.rt_token', $script);
        $this->assertStringContainsString('Hoş geldin', $script);
        $this->assertStringContainsString('coin_balance', $script);
        $this->assertStringNotContainsString('VIP', $script);
        $this->assertStringNotContainsString('vip_until', $script);
    }
}
