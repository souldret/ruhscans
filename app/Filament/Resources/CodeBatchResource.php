<?php

namespace App\Filament\Resources;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Filament\Resources\CodeBatchResource\Pages;
use App\Filament\Support\AdminAccess;
use App\Models\CodeBatch;
use App\Models\CodeExport;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CodeBatchResource extends Resource
{
    protected static ?string $model = CodeBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Kod Partileri';

 protected static ?string $modelLabel = 'Parti';

 protected static ?string $pluralModelLabel = 'Kod partileri';

 protected static ?string $navigationGroup = 'Ekonomi';

 protected static ?int $navigationSort = 2;

 public static function canViewAny(): bool
 {
 return AdminAccess::admin();
 }

    public static function form(Form $form): Form
 {
 return $form->schema([]);
 }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'codes as used_count' => fn ($query) => $query->where('durum', CodeStatus::Kullanildi->value),
                'codes as left_count' => fn ($query) => $query->where('durum', CodeStatus::Aktif->value),
                'codes as cancelled_count' => fn ($query) => $query->where('durum', CodeStatus::Iptal->value),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('ad')->label('Parti')->searchable(),
                Tables\Columns\TextColumn::make('tur')->label('Tür')->formatStateUsing(fn ($state) => $state instanceof CodeKind ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('deger')->label('Değer'),
                Tables\Columns\TextColumn::make('adet')->label('Üretilen'),
                Tables\Columns\TextColumn::make('used_count')->label('Kullanılan'),
                Tables\Columns\TextColumn::make('left_count')->label('Kalan'),
                Tables\Columns\TextColumn::make('cancelled_count')->label('İptal'),
                Tables\Columns\TextColumn::make('son_kullanma')->label('Son kullanma')->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('indir')
                    ->label('Tek seferlik indir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (CodeBatch $record) => self::readyExport($record) !== null)
                    ->url(fn (CodeBatch $record) => route('admin.codes.csv', ['token' => self::readyExport($record)?->token]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('iptal')
                    ->label('Kullanılmamışları iptal et')
                    ->requiresConfirmation()
                    ->visible(fn (CodeBatch $record) => (int) $record->left_count > 0)
                    ->action(function (CodeBatch $record) {
                        $record->codes()->where('durum', CodeStatus::Aktif->value)->update([
                            'durum' => CodeStatus::Iptal->value,
                        ]);
                    }),
            ]);
    }

    public static function readyExport(CodeBatch $batch): ?CodeExport
 {
 return $batch->exports()
 ->whereNull('downloaded_at')
 ->where('expires_at', '>', now())
 ->latest('id')
 ->first();
 }

 public static function getPages(): array
    {
        return [
            'index' => Pages\ListCodeBatches::route('/'),
        ];
    }
}
