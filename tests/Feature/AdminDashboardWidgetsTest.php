<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Enums\SupportThreadStatus;
use App\Filament\Widgets\CoinPackagesSnapshot;
use App\Filament\Widgets\DailyOverview;
use App\Filament\Widgets\OpenSupport;
use App\Filament\Widgets\RecentCoinActivity;
use App\Filament\Widgets\RecentGames;
use App\Models\CoinPackage;
use App\Models\Game;
use App\Models\SupportThread;
use App\Models\User;
use App\Services\CoinService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_render_for_an_admin(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        foreach ([
            DailyOverview::class,
            RecentGames::class,
            RecentCoinActivity::class,
            OpenSupport::class,
            CoinPackagesSnapshot::class,
        ] as $widget) {
            Livewire::test($widget)->assertOk();
        }
    }

    public function test_dashboard_widgets_render_with_rows(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $this->actingAs($admin);

        Game::factory()->create(['title' => 'Gece Vardiyasi', 'is_published' => true]);
        app(CoinService::class)->credit($member, 40, CoinTransactionType::Bonus, note: 'widget');

        $package = CoinPackage::query()->create([
            'name' => 'Deneme paketi',
            'coin_amount' => 100,
            'bonus_coin' => 10,
            'price_label' => '49 TL',
            'url' => 'https://example.test/paket',
            'sort_order' => 1,
            'is_active' => true,
            'is_featured' => false,
        ]);
        $package->clicks()->create(['user_id' => $member->id, 'ip_hash' => hash('sha256', '127.0.0.1')]);

        $thread = SupportThread::query()->create([
            'user_id' => $member->id,
            'subject' => 'Kod gelmedi',
            'status' => SupportThreadStatus::Open,
            'last_message_at' => now(),
            'unread_by_admin' => true,
            'unread_by_user' => false,
        ]);

        Livewire::test(RecentGames::class)->assertSee('Gece Vardiyasi');
        Livewire::test(RecentCoinActivity::class)->assertSee('+40');
        Livewire::test(OpenSupport::class)
            ->assertSee('Kod gelmedi')
            ->assertSeeHtml(route('filament.admin.resources.support-threads.view', ['record' => $thread]));
        Livewire::test(CoinPackagesSnapshot::class)->assertSee('Deneme paketi')->assertSee('49 TL');
        Livewire::test(DailyOverview::class)->assertSee('Paket tıklaması')->assertOk();
    }
}
