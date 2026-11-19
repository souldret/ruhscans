<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\EnsureAdminTwoFactor;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(LoginResponse::class, function () {
            return new class implements LoginResponse, Responsable
            {
                public function toResponse($request)
                {
                    $user = $request->user();

                    if ($user !== null && $user->hasRole('admin')) {
            $route = $user->hasConfirmedTwoFactor()
                ? 'admin.two-factor.challenge'
                : 'admin.two-factor';

            return redirect()->route($route);
        }

        return redirect()->intended(filament()->getUrl());
                }
            };
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->brandName('ruhscans')
            ->brandLogo(asset('images/ruhscans-mark.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('favicon.svg'))
            ->darkMode(true, true)
            ->colors([
                'primary' => Color::hex('#FF2E88'),
            ])
            ->navigationGroups([
                'İçerik',
                'Ekonomi',
                'Kullanıcılar',
                'Sistem',
            ])
            ->navigationItems([
                NavigationItem::make('Horizon')
                    ->url('/horizon', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-queue-list')
                    ->group('Sistem')
                    ->sort(90)
                    ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
            Pages\Dashboard::class,
        ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => Blade::render(<<<'HTML'
                <style>
                    :root { color-scheme: dark; }
                    .fi-body { background: #120910; }
                </style>
            HTML))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
            AuthenticateAdminPanel::class,
            EnsureAdminTwoFactor::class,
        ]);
    }
}
