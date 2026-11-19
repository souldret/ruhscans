<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class EconomySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Sonteklif';

    protected static ?string $title = 'Sonteklif bağlantısı';

    protected static ?string $navigationGroup = 'Ekonomi';

    protected static string $view = 'filament.pages.economy-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'sonteklif_url' => SiteSetting::get('sonteklif_url', (string) config('economy.sonteklif_url')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('sonteklif_url')
                    ->label('Sonteklif bağlantısı')
                    ->url()
                    ->required()
                    ->maxLength(500),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        SiteSetting::put('sonteklif_url', $state['sonteklif_url']);

        Notification::make()->title('Kaydedildi')->success()->send();
    }
}
