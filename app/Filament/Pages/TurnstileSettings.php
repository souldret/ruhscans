<?php

namespace App\Filament\Pages;

use App\Services\Turnstile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TurnstileSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Doğrulama';

    protected static ?string $title = 'Cloudflare doğrulama';

    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.turnstile';

    public ?string $site_key = '';

    public ?string $secret = '';

    public function mount(): void
    {
        $this->form->fill([
            'site_key' => app(Turnstile::class)->siteKey(),
            'secret' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('site_key')
                ->label('Site anahtarı')
                ->helperText('Cloudflare Turnstile panelindeki site key.')
                ->maxLength(255),
            TextInput::make('secret')
                ->label('Gizli anahtar')
                ->password()
                ->revealable()
                ->helperText(app(Turnstile::class)->secret() !== ''
                    ? 'Kayıtlı bir gizli anahtar var. Değiştirmek için yenisini yaz, boş bırakırsan durur.'
                    : 'Cloudflare Turnstile panelindeki secret key.')
                ->maxLength(255),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $site = trim((string) ($data['site_key'] ?? ''));
        $secret = trim((string) ($data['secret'] ?? ''));
        $current = app(Turnstile::class)->secret();
        $kept = is_string($current) ? $current : '';

        if ($secret === '') {
            $secret = $kept;
        }

        if (($site === '') !== ($secret === '')) {
            Notification::make()
                ->title('İki anahtar da dolu olmalı ya da ikisi de boş kalmalı.')
                ->danger()
                ->send();

            return;
        }

        app(Turnstile::class)->store($site, $secret);

        Notification::make()
            ->title(app(Turnstile::class)->enabled() ? 'Doğrulama açıldı.' : 'Doğrulama kapatıldı.')
            ->success()
            ->send();
    }
}
