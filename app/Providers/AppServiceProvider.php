<?php

namespace App\Providers;

use App\Support\AdultGate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::guessPolicyNamesUsing(
            fn (string $modelClass) => 'App\\Policies\\'.class_basename($modelClass).'Policy'
        );

        Carbon::setLocale(config('app.locale'));

        View::composer('*', function ($view): void {
            $view->with('adult', AdultGate::confirmed(request()));
        });

        $this->localSignedDownloads();
    }

    private function localSignedDownloads(): void
    {
        $disk = Storage::disk('local');

        if ($disk->providesTemporaryUrls()) {
            return;
        }

        $disk->buildTemporaryUrlsUsing(function (string $path, $expiration, array $options) {
            $file = $options['file'] ?? null;
            abort_unless($file, 404);

            $parameters = ['file' => $file];
            $userId = $options['user'] ?? $options['uye'] ?? null;

            if (is_numeric($userId)) {
                $parameters['uye'] = (int) $userId;
            }


        $ip = $options['ip'] ?? null;

        if (is_string($ip) && $ip !== '') {
            $parameters['ip'] = $ip;
        }
            return URL::temporarySignedRoute('downloads.signed', $expiration, $parameters);
        });
    }
}
