<?php

namespace Tests\Feature;

use App\Enums\SupportThreadStatus;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_is_linked_from_the_menus_and_coin_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Destek', false)
            ->assertSee(route('support.index'), false);

        $this->get(route('coins'))
            ->assertOk()
            ->assertSee('Destek için buraya', false);

        $this->get(route('support.index'))
            ->assertOk()
            ->assertSee('Bir sorun mu var?', false)
            ->assertSee('Giriş yapıp yaz', false);
    }

    public function test_a_member_writes_and_the_admin_reply_comes_back(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($member)
            ->post(route('support.store'), [
                'subject' => 'Kod gelmedi',
                'body' => 'Odedim ama kod yok.',
            ])
            ->assertRedirect();

        $thread = SupportThread::query()->firstOrFail();
        $this->assertTrue($thread->unread_by_admin);
        $this->assertSame(SupportThreadStatus::Open, $thread->status);

        $this->actingAs($member)
            ->get(route('support.show', $thread))
            ->assertOk()
            ->assertSee('Kod gelmedi', false)
            ->assertSee('Odedim ama kod yok.', false);

        $thread->post($admin, 'Kodunu yeniden gonderiyorum.');

        $this->actingAs($member)
            ->get(route('support.index'))
            ->assertOk()
            ->assertSee('Kod gelmedi', false);

        $this->actingAs($member)
            ->get(route('support.show', $thread))
            ->assertOk()
            ->assertSee('Kodunu yeniden gonderiyorum.', false);

        $thread->refresh();
        $this->assertFalse($thread->unread_by_user);
        $this->assertSame(2, SupportMessage::query()->count());
    }

    public function test_another_member_cannot_read_or_reply(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $thread = SupportThread::query()->create([
            'user_id' => $owner->id,
            'subject' => 'Bakiye',
            'status' => SupportThreadStatus::Open,
        ]);
        $thread->post($owner, 'Coinim eksik.');

        $this->actingAs($other)->get(route('support.show', $thread))->assertForbidden();
        $this->actingAs($other)
            ->post(route('support.reply', $thread), ['body' => 'Ben de bakayim.'])
            ->assertForbidden();
    }

    public function test_a_closed_thread_rejects_another_member_message(): void
    {
        $member = User::factory()->create();
        $thread = SupportThread::query()->create([
            'user_id' => $member->id,
            'subject' => 'Bitti',
            'status' => SupportThreadStatus::Open,
        ]);
        $thread->post($member, 'Sorun cozuldu.');

        $this->actingAs($member)
            ->post(route('support.close', $thread))
            ->assertRedirect(route('support.show', $thread));

        $this->actingAs($member)
            ->from(route('support.show', $thread))
            ->post(route('support.reply', $thread), ['body' => 'Tekrar aciyorum.'])
            ->assertForbidden();

        $this->assertSame(1, $thread->messages()->count());
    }

    public function test_an_empty_message_is_rejected(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->from(route('support.index'))
            ->post(route('support.store'), ['subject' => 'Kisa', 'body' => ' '])
            ->assertRedirect(route('support.index'))
            ->assertSessionHasErrors('body');

        $this->assertSame(0, SupportThread::query()->count());
    }

    public function test_an_admins_own_ticket_stays_a_member_message(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('support.store'), [
                'subject' => 'Kendi kodum',
                'body' => 'Yonetici hesabimdan yaziyorum.',
            ])
            ->assertRedirect();

        $thread = SupportThread::query()->firstOrFail();
        $message = $thread->messages()->firstOrFail();

        $this->assertFalse($message->is_staff);
        $this->assertTrue($thread->unread_by_admin);
        $this->assertSame(SupportThreadStatus::Open, $thread->status);
    }
}
