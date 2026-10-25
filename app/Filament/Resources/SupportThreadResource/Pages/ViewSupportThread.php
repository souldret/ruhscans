<?php

namespace App\Filament\Resources\SupportThreadResource\Pages;

use App\Enums\SupportThreadStatus;
use App\Filament\Resources\SupportThreadResource;
use App\Models\SupportThread;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportThread extends ViewRecord
{
    protected static string $resource = SupportThreadResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $actor = auth()->user();

        if ($actor instanceof User && $this->record instanceof SupportThread) {
            $this->record->markReadFor($actor);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Forms\Components\Textarea::make('body')->label('Yanıt')->required()->minLength(2)->maxLength(4000),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User && $this->record instanceof SupportThread, 403);
                    $this->record->post($actor, $data['body']);
                    $this->record->unsetRelation('messages');
                    $this->record->load(['messages.user', 'user']);
                    Notification::make()->title('Yanıt gönderildi')->success()->send();
                }),
            Actions\Action::make('close')
                ->label('Kapat')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof SupportThread && $this->record->status !== SupportThreadStatus::Closed)
                ->action(function (): void {
                    abort_unless($this->record instanceof SupportThread, 404);
                    $this->record->close();
                    $this->record->refresh();
                    Notification::make()->title('Yazışma kapatıldı')->success()->send();
                }),
        ];
    }
}
