<?php

namespace App\Filament\Auth;

use App\Services\Security\TurnstileService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class Login extends BaseLogin
{
    public string $turnstileToken = '';

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();
        $email = (string) ($data['email'] ?? '');
        $ip = (string) (request()->ip() ?? 'unknown');
        $scope = sha1($ip.'|'.$email);
        $attemptKey = 'admin-login:attempt:'.$scope;
        $failKey = 'admin-login:fail:'.$scope;
        $lockKey = 'admin-login:lock:'.$scope;

        if (Cache::has($lockKey)) {
            $this->throwFailureValidationException();

            return null;
        }

        try {
            RateLimiter::attempt($attemptKey, 5, static fn (): null => null, 60);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if (! app(TurnstileService::class)->verify($this->turnstileToken)) {
            Notification::make()
                ->danger()
                ->title('Doğrulama başarısız. Lütfen tekrar deneyin.')
                ->send();
            $this->dispatch('turnstile-reset');

            return null;
        }

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $failures = (int) Cache::increment($failKey);

            if ($failures === 1) {
                Cache::put($failKey, 1, now()->addMinutes(15));
            }

            activity('admin-login')
                ->withProperties(['email' => $email, 'ip' => $ip])
                ->log('Başarısız panel girişi');

            if ($failures >= 10) {
                Cache::put($lockKey, true, now()->addMinutes(15));
                Cache::forget($failKey);
            }

            $this->throwFailureValidationException();

            return null;
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();

            return null;
        }

        Cache::forget($failKey);
        Cache::forget($lockKey);
        session()->regenerate();

        activity('admin-login')
            ->withProperties([
                'email' => $email,
                'ip' => $ip,
                'user_id' => $user?->id,
            ])
            ->log('Başarılı panel girişi');

        return app(LoginResponse::class);
    }
}
