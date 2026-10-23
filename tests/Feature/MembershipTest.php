<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_vip_is_active_only_while_the_date_is_in_the_future(): void
    {
        $active = User::factory()->vip()->create();
        $expired = User::factory()->create(['vip_until' => now()->subDay()]);
        $plain = User::factory()->create();

        $this->assertTrue($active->isVip());
        $this->assertFalse($expired->isVip());
        $this->assertFalse($plain->isVip());

        $this->assertTrue(User::query()->vip()->whereKey($active->id)->exists());
        $this->assertFalse(User::query()->vip()->whereKey($expired->id)->exists());
    }

    public function test_only_admins_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/yonetim')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/yonetim')->assertOk();
    }

    public function test_email_verification_can_be_switched_off(): void
    {
        config(['membership.verification' => false]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public function test_email_verification_blocks_unverified_users_when_enabled(): void
    {
        config(['membership.verification' => true]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }
}
