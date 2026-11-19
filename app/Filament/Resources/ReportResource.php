<?php

namespace App\Filament\Resources;

use App\Actions\Content\HideReportedContent;
use App\Enums\ReportStatus;
use App\Filament\Resources\ChapterResource;
use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\SeriesResource;
use App\Filament\Support\AdminAccess;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\Series;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Şikayetler';

    protected static ?string $modelLabel = 'Şikayet';

    protected static ?string $pluralModelLabel = 'Şikayet kuyruğu';

    protected static ?string $navigationGroup = 'İçerik';

 protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
 {
 return AdminAccess::admin();
 }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Bildiren')
                ->relationship('user', 'email')
                ->searchable(),
            Forms\Components\Select::make('reportable_type')
                ->label('Tür')
                ->options([
                    Series::class => 'Seri',
                    Chapter::class => 'Bölüm',
                ])
                ->required(),
            Forms\Components\TextInput::make('reportable_id')->label('Kayıt')->numeric()->required(),
            Forms\Components\Textarea::make('reason')->label('Gerekçe')->required()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('Durum')
                ->options(collect(ReportStatus::cases())->mapWithKeys(fn (ReportStatus $status) => [$status->value => $status->label()]))
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
 ->defaultSort('created_at', 'desc')
 ->modifyQueryUsing(fn ($query) => $query->with(['user', 'reportable']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Bildiren'),
                Tables\Columns\TextColumn::make('reportable_type')->label('Tür')->formatStateUsing(fn (string $state) => class_basename($state)),
                Tables\Columns\TextColumn::make('reason')->label('Gerekçe')->limit(60),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(ReportStatus::cases())->mapWithKeys(fn (ReportStatus $status) => [$status->value => $status->label()]))
                    ->default(ReportStatus::Bekliyor->value),
            ])
            ->actions([
                Tables\Actions\Action::make('ac')
                    ->label('İçeriği aç')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function (Report $record) {
                        $target = $record->reportable;

                        if ($target instanceof Series) {
                            return SeriesResource::getUrl('edit', ['record' => $target]);
                        }

                        if ($target instanceof Chapter) {
                            return ChapterResource::getUrl('edit', ['record' => $target]);
                        }

                        return null;
                    })
                    ->visible(fn (Report $record) => $record->reportable instanceof Series || $record->reportable instanceof Chapter),
                Tables\Actions\Action::make('gizle')
                    ->label('Gizle')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Bekliyor)
                    ->action(fn (Report $record) => app(HideReportedContent::class)($record, auth()->user())),
                Tables\Actions\Action::make('cozuldu')
                    ->label('Çözüldü')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Report $record) => $record->status === ReportStatus::Bekliyor)
                    ->action(fn (Report $record) => $record->update([
                        'status' => ReportStatus::Incelendi,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ])),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
