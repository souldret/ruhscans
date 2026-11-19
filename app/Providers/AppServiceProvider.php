<?php

namespace App\Providers;

use App\Services\Security\ClientAddress;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        RateLimiter::for('reads', function (Request $request) {
            return Limit::perMinute((int) config('security.read_rate.per_minute', 40))
                ->by(app(ClientAddress::class)->key($request));
        });
    }
}
