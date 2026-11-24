<?php

namespace App\Providers;

use App\Models\Chapter;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Observers\CatalogCacheObserver;
use App\Services\Security\ClientAddress;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->hasRole(User::ROLE_ADMIN) && ! $user->isBlocked()) {
                return true;
            }

            return null;
        });

 Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->symbols());

        $observer = CatalogCacheObserver::class;
        Series::observe($observer);
        Chapter::observe($observer);
        Tag::observe($observer);

        RateLimiter::for('reads', function (Request $request) {
            return Limit::perMinute((int) config('security.read_rate.per_minute', 40))
                ->by(app(ClientAddress::class)->key($request));
        });
    }
}
