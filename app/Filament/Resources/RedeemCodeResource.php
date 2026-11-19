<?php

namespace App\Filament\Resources;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Filament\Resources\RedeemCodeResource\Pages;
use App\Filament\Support\AdminAccess;
use App\Models\RedeemCode;
use App\Services\Economy\CodeHasher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RedeemCodeResource extends Resource
{
    protected static ?string $model = RedeemCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Kodlar';

    protected static ?string $modelLabel = 'Kod';

    protected static ?string $pluralModelLabel = 'Kodlar';

    protected static ?string $navigationGroup = 'Ekonomi';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return AdminAccess::admin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['batch', 'redeemer']))
            ->columns([
                Tables\Columns\TextColumn::make('batch.ad')->label('Parti')->searchable(),
                Tables\Columns\TextColumn::make('tur')->label('Tür')->formatStateUsing(fn ($state) => $state instanceof CodeKind ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('deger')->label('Değer'),
                Tables\Columns\TextColumn::make('durum')->label('Durum')->badge()->formatStateUsing(fn ($state) => $state instanceof CodeStatus ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('son_dort')->label('Son 4 hane')->searchable(),
                Tables\Columns\TextColumn::make('redeemer.email')->label('Kullanan üye'),
                Tables\Columns\TextColumn::make('kullanim_tarihi')->label('Kullanım')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('son_kullanma')->label('Son kullanma')->dateTime('d.m.Y H:i')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('durum')
                    ->label('Durum')
                    ->options(collect(CodeStatus::cases())->mapWithKeys(fn (CodeStatus $status) => [$status->value => $status->label()])),
                Tables\Filters\SelectFilter::make('tur')
                    ->label('Tür')
                    ->options(collect(CodeKind::cases())->mapWithKeys(fn (CodeKind $kind) => [$kind->value => $kind->label()])),
            ])
            ->headerActions([
                Tables\Actions\Action::make('ara')
                    ->label('Kod ara')
                    ->form([
                        Forms\Components\TextInput::make('kod')->label('Kod')->required(),
                    ])
                    ->action(function (array $data, CodeHasher $hasher) {
                        $code = RedeemCode::query()
                            ->with(['batch', 'redeemer'])
                            ->where('kod_hash', $hasher->hashPlain($data['kod']))
                            ->first();

                        if ($code === null) {
                            Notification::make()->title('Kod bulunamadı')->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title($code->durum->label().' · son 4: '.$code->son_dort)
                            ->body(($code->batch?->ad ?? 'Parti').' · '.($code->redeemer?->email ?? 'kullanılmamış'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('iptal')
                    ->label('İptal et')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (RedeemCode $record) => $record->durum === CodeStatus::Aktif)
                    ->action(fn (RedeemCode $record) => $record->update(['durum' => CodeStatus::Iptal])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedeemCodes::route('/'),
        ];
    }
}
