<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use PragmaRX\Google2FA\Google2FA;

class TwoFactor extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

 protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'İki aşamalı doğrulama';

    protected static ?string $title = 'İki aşamalı doğrulama';

    protected static string $view = 'filament.pages.two-factor';

    public ?string $secret = null;

    public ?string $code = null;

    public function mount(): void
    {
        $user = $this->user();

        if ($user->two_factor_confirmed_at === null && filled($user->two_factor_secret)) {
            $this->secret = $user->two_factor_secret;
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Uygulama kodu')
                ->required()
                ->numeric()
                ->length(6),
        ]);
    }

    public function start(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = $this->user();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->secret = $secret;
        $this->code = null;
    }

    public function confirm(): void
    {
        $user = $this->user();
        $secret = $user->two_factor_secret;

        if (! is_string($secret) || ! (new Google2FA)->verifyKey($secret, (string) $this->code, 1)) {
            Notification::make()->title('Kod geçersiz')->danger()->send();

            return;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->secret = null;
        $this->code = null;

        Notification::make()->title('İki aşamalı doğrulama açıldı')->success()->send();
    }

    public function disable(): void
    {
        Notification::make()->title('Yönetimde iki aşamalı doğrulama kapatılamaz')->danger()->send();
    }

    public function otpauth(): string
    {
        $user = $this->user();

        return (new Google2FA)->getQRCodeUrl(
            (string) config('app.name'),
            $user->email,
            (string) ($this->secret ?: $user->two_factor_secret),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disable')
                ->label('Kapat')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->user()->two_factor_confirmed_at !== null)
                ->action(fn () => $this->disable()),
        ];
    }

    private function user(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
