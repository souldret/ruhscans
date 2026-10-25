<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_covers_stay_blurred_until_the_adult_cookie_is_set(): void
    {
        $game = Game::factory()->create(['title' => 'Gumus Bahce', 'is_published' => true]);

        $this->get(route('games.show', $game))
            ->assertOk()
            ->assertSee('18 yaşındayım', false)
            ->assertSee('blur-lg', false);

        $this->withUnencryptedCookie('adult', '1')
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertDontSee('18 yaşındayım', false);
    }

    public function test_search_bots_see_covers_without_the_gate(): void
    {
        $game = Game::factory()->create(['is_published' => true]);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get(route('games.show', $game))
            ->assertOk()
            ->assertDontSee('18 yaşındayım', false);
    }

    public function test_adult_confirmation_sets_a_year_cookie(): void
    {
        $this->from(route('home'))
            ->post(route('adult.confirm'))
            ->assertRedirect(route('home'))
            ->assertPlainCookie('adult', '1');
    }

    public function test_cookie_notice_sets_a_plain_cookie(): void
    {
        $this->from(route('home'))
            ->post(route('cookies.notice'))
            ->assertRedirect(route('home'))
            ->assertPlainCookie('notice', '1');
    }

    public function test_takedown_form_rejects_empty_and_honeypot_submissions(): void
    {
        $this->from(route('legal.takedown'))
            ->post(route('legal.takedown.store'), [])
            ->assertRedirect(route('legal.takedown'))
            ->assertSessionHasErrors(['name', 'email', 'url', 'reason']);

        $this->from(route('legal.takedown'))
            ->post(route('legal.takedown.store'), [
                'name' => 'Ada',
                'email' => 'ada@example.com',
                'url' => 'https://ornek.test/oyun/x',
                'reason' => 'Hak sahibiyim.',
                'company' => 'bot',
            ])
            ->assertRedirect(route('legal.takedown'))
            ->assertSessionHasErrors('company');

        $this->from(route('legal.takedown'))
            ->post(route('legal.takedown.store'), [
                'name' => 'Ada',
                'email' => 'ada@example.com',
                'url' => 'https://ornek.test/oyun/x',
                'reason' => 'Hak sahibiyim.',
            ])
            ->assertRedirect(route('legal.takedown'))
            ->assertSessionHas('sent', true);
    }

    public function test_vip_page_and_health_check_are_public(): void
    {
        $this->get('/vip')->assertStatus(301)->assertRedirect(route('coins'));
        $this->get('/up')->assertOk();
    }

    public function test_legal_pages_and_sitemap_are_public(): void
    {
        foreach (['legal.terms', 'legal.privacy', 'legal.cookies', 'legal.about', 'legal.takedown'] as $name) {
            $this->get(route($name))->assertOk()->assertSee('Taslak', false);
        }

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml');

        $this->get(route('robots'))
            ->assertOk()
            ->assertSee('Disallow: /yonetim', false);
    }

    public function test_registration_rejects_a_filled_honeypot_and_a_weak_password(): void
    {
        $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'Gizli-Parola-12',
            'password_confirmation' => 'Gizli-Parola-12',
            'company' => 'bot',
        ])->assertSessionHasErrors('company');

        $this->post('/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'kisa',
            'password_confirmation' => 'kisa',
        ])->assertSessionHasErrors('password');
    }

    public function test_responses_carry_a_content_security_policy(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('Content-Security-Policy');
    }
}
