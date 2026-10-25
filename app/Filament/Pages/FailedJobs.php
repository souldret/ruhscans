<?php

namespace App\Filament\Pages;

use App\Models\FailedJob;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class FailedJobs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Başarısız işler';

    protected static ?string $title = 'Başarısız işler';

    protected static ?string $navigationGroup = 'Sistem';

    protected static string $view = 'filament.pages.failed-jobs';

    public function table(Table $table): Table
    {
        return $table
            ->query(FailedJob::query())
            ->columns([
                Tables\Columns\TextColumn::make('uuid')->label('İş')->limit(12),
                Tables\Columns\TextColumn::make('queue')->label('Kuyruk'),
                Tables\Columns\TextColumn::make('failed_at')->label('Zaman')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('exception')->label('Hata')->formatStateUsing(
                    fn (?string $state) => $state === null ? '—' : strtok($state, "\n")
                )->limit(80),
            ])
            ->actions([
                Tables\Actions\Action::make('retry')
                    ->label('Yeniden dene')
                    ->action(function (FailedJob $record): void {
                        Artisan::call('queue:retry', ['id' => [$record->uuid]]);
                    }),
            ])
            ->defaultSort('id', 'desc');
    }
}
