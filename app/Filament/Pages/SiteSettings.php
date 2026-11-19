<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Ops\SiteOperations;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Site ayarları';

    protected static ?string $title = 'Site ayarları';

    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function mount(SiteOperations $ops): void
    {
        $this->form->fill([
            'panic_url' => $ops->panicUrl(),
            'sonteklif_url' => $ops->sonteklifUrl(),
            'announcement' => $ops->announcement(),
            'maintenance_mode' => $ops->maintenance(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('panic_url')
                    ->label('Panik adresi')
                    ->url()
                    ->required()
                    ->maxLength(500)
                    ->helperText('Kullanıcının kendi adresi yoksa bu nötr sayfa açılır.'),
                TextInput::make('sonteklif_url')
                    ->label('Sonteklif bağlantısı')
                    ->url()
                    ->required()
                    ->maxLength(500),
                Textarea::make('announcement')
                    ->label('Duyuru bandı')
                    ->rows(3)
                    ->maxLength(280)
                    ->helperText('Boş bırakılırsa bant gizlenir.'),
                Toggle::make('maintenance_mode')
                    ->label('Bakım modu')
                    ->helperText('Açıkken ziyaretçi bakım sayfası görür. Yönetim paneli açık kalır.'),
            ])
            ->statePath('data');
    }

    public function save(SiteOperations $ops): void
    {
        $state = $this->form->getState();
        $maintenance = (bool) ($state['maintenance_mode'] ?? false);

        SiteSetting::put('panic_url', $state['panic_url']);
        SiteSetting::put('sonteklif_url', $state['sonteklif_url']);
        SiteSetting::put('announcement', $state['announcement'] ?? '');

        if ($maintenance && ! $ops->maintenance()) {
            $ops->enableMaintenance();
        }

        if (! $maintenance && $ops->maintenance()) {
            $ops->disableMaintenance();
        }

        Notification::make()->title('Kaydedildi')->success()->send();
    }
}
