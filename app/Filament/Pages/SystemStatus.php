<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminAccess;
use App\Services\Ops\SystemProbe;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Sistem Durumu';

    protected static ?string $title = 'Sistem Durumu';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.system-status';

    public array $snapshot = [];

    public static function canAccess(): bool
    {
        return AdminAccess::admin();
    }

    public function mount(SystemProbe $probe): void
    {
        $this->snapshot = $probe->snapshot();
    }

    public function refreshStatus(SystemProbe $probe): void
    {
        $this->snapshot = $probe->snapshot();
    }

    public function probe(string $target, SystemProbe $probe): void
    {
        $result = $probe->check($target);

        Notification::make()
            ->title($result['message'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();
    }
}
