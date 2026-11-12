<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Önizle')
                ->icon('heroicon-o-eye')
                ->url(function (): string {
                    $record = $this->getRecord();

                    if (! $record instanceof Game) {
                        return route('games.index');
                    }

                    return URL::temporarySignedRoute('games.show', now()->addHour(), [
                        'game' => $record,
                        'onizle' => 1,
                    ]);
                })
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
