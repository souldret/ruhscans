<?php

namespace Tests\Feature;

use App\Filament\Resources\CoinPackageResource;
use App\Models\CoinPackage;
use App\Models\CoinPackageClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinPackagePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_inactive_package_stays_off_the_page(): void
    {
        CoinPackage::factory()->create(['name' => 'Gizli Paket', 'is_active' => false]);
        CoinPackage::factory()->create(['name' => 'Acik Paket', 'is_active' => true]);

        $this->get(route('coins'))
            ->assertOk()
            ->assertSee('Acik Paket')
            ->assertDontSee('Gizli Paket');
    }

    public function test_an_http_link_cannot_be_saved(): void
    {
        $this->assertSame('regex:/^https:\/\/.+/', CoinPackageResource::httpsRule());

        $package = CoinPackage::factory()->create(['url' => 'http://odeme.example/paket']);

        $this->get(route('coins.go', $package))->assertNotFound();
        $this->assertSame(0, CoinPackageClick::query()->count());
    }

    public function test_the_go_route_records_the_click_and_redirects(): void
    {
        $package = CoinPackage::factory()->create(['url' => 'https://odeme.example/paket']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('coins.go', $package))
            ->assertRedirect('https://odeme.example/paket');

        $click = CoinPackageClick::query()->firstOrFail();
        $this->assertSame($package->id, $click->coin_package_id);
        $this->assertSame($user->id, $click->user_id);
        $this->assertSame(hash('sha256', '127.0.0.1'), $click->ip_hash);
    }

    public function test_the_old_vip_address_redirects_permanently(): void
    {
        $this->get('/vip')->assertStatus(301)->assertRedirect('/coin-paketleri');
    }

    public function test_the_page_shows_an_empty_state_without_packages(): void
    {
        $this->get(route('coins'))
            ->assertOk()
            ->assertSee('Şu an satışta paket yok')
            ->assertSee('Nasıl çalışır')
            ->assertSee('Kodları yalnızca');
    }
}
