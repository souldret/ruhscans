<?php

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Filament\Resources\ReportResource;
use App\Filament\Support\AdminAccess;
use App\Models\Report;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentReports extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Son şikayetler';

    public static function canView(): bool
    {
        return AdminAccess::admin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Report::query()->with('user')->latest()->limit(8))
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('user.email')->label('Bildiren'),
                Tables\Columns\TextColumn::make('reason')->label('Gerekçe')->limit(50),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('ac')
                    ->label('Aç')
                    ->url(fn (Report $record) => ReportResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
