<?php

namespace App\Filament\Widgets;

use App\Enums\SupportThreadStatus;
use App\Filament\Resources\SupportThreadResource;
use App\Models\SupportThread;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OpenSupport extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Açık destek';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SupportThread::query()
                    ->with('user')
                    ->where('status', '!=', SupportThreadStatus::Closed)
                    ->latest('last_message_at')
            )
            ->paginated([8])
            ->defaultPaginationPageOption(8)
            ->emptyStateHeading('Açık yazışma yok')
            ->columns([
                Tables\Columns\IconColumn::make('unread_by_admin')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-envelope')
                    ->falseIcon('heroicon-o-envelope-open')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('subject')->label('Konu')->limit(28),
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->placeholder('—')->limit(16),
                Tables\Columns\TextColumn::make('status')->label('Durum')->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('last_message_at')->label('Son')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Aç')
                    ->url(fn (SupportThread $record): string => SupportThreadResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
