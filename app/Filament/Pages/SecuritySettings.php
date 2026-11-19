<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\User;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SecuritySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Güvenlik';

    protected static ?string $title = 'Okuma güvenliği';

    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.security-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function mount(): void
    {
        $stored = SiteSetting::get('watermark_enabled');

        $this->form->fill([
            'watermark_enabled' => $stored === null
                ? (bool) config('security.watermark')
                : $stored === '1',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('watermark_enabled')
                    ->label('Kullanıcıya özel filigran')
                    ->helperText('Açıkken sayfa listesi kullanıcı kimliğini taşır.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        SiteSetting::put('watermark_enabled', ($state['watermark_enabled'] ?? false) ? '1' : '0');

        Notification::make()->title('Kaydedildi')->success()->send();
    }
}
