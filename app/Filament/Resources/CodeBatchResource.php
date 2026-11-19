<?php

namespace App\Filament\Resources;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Filament\Resources\CodeBatchResource\Pages;
use App\Models\CodeBatch;
use App\Models\User;
use App\Services\Economy\CodeGenerator;
use App\Services\Economy\CodeHasher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CodeBatchResource extends Resource
{
    protected static ?string $model = CodeBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Kodlar';

    protected static ?string $modelLabel = 'Parti';

    protected static ?string $pluralModelLabel = 'Kod partileri';

    protected static ?string $navigationGroup = 'Ekonomi';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('ad')->label('Parti adı')->disabled(),
            Forms\Components\TextInput::make('tur')->label('Tür')->disabled(),
            Forms\Components\TextInput::make('deger')->label('Değer')->disabled(),
            Forms\Components\DateTimePicker::make('son_kullanma')->label('Son kullanma')->disabled(),
            Forms\Components\TextInput::make('adet')->label('Üretilen')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ad')->label('Parti')->searchable(),
                Tables\Columns\TextColumn::make('tur')->label('Tür')->formatStateUsing(fn ($state) => $state instanceof CodeKind ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('deger')->label('Değer'),
                Tables\Columns\TextColumn::make('adet')->label('Üretilen'),
                Tables\Columns\TextColumn::make('used')->label('Kullanılan')->state(fn (CodeBatch $record) => $record->usedCount()),
                Tables\Columns\TextColumn::make('left')->label('Kalan')->state(fn (CodeBatch $record) => $record->activeCount()),
                Tables\Columns\TextColumn::make('son_kullanma')->label('Son kullanma')->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('uret')
                    ->label('Toplu üretim')
                    ->form([
                        Forms\Components\TextInput::make('ad')->label('Parti adı')->required()->maxLength(120),
                        Forms\Components\TextInput::make('adet')->label('Adet')->numeric()->minValue(1)->maxValue(5000)->required(),
                        Forms\Components\Select::make('tur')->label('Tür')->options([
                            CodeKind::RuhTasi->value => 'Ruh taşı',
                            CodeKind::Vip->value => 'VIP gün',
                        ])->required(),
                        Forms\Components\TextInput::make('deger')->label('Değer')->helperText('Ruh taşı adedi veya VIP gün sayısı')->numeric()->minValue(1)->required(),
                        Forms\Components\DateTimePicker::make('son_kullanma')->label('Son kullanma'),
                    ])
                    ->action(function (array $data, CodeGenerator $generator) {
                        $result = $generator->generate(
                            (int) $data['adet'],
                            CodeKind::from($data['tur']),
                            (int) $data['deger'],
                            filled($data['son_kullanma'] ?? null) ? Carbon::parse($data['son_kullanma']) : null,
                            $data['ad'],
                            auth()->user(),
                        );

                        $token = Str::random(40);
                        session()->put('code_csv.'.$token, $generator->csv($result['codes']));

                        Notification::make()
                            ->title($result['batch']->adet.' kod üretildi')
                            ->body('Düz kodlar yalnızca bu indirmede var.')
                            ->success()
                            ->send();

                        return redirect()->route('admin.codes.csv', ['token' => $token, 'batch' => $result['batch']->id]);
                    }),
                Tables\Actions\Action::make('ara')
                    ->label('Hash ile ara')
                    ->form([
                        Forms\Components\TextInput::make('kod')->label('Kod')->required(),
                    ])
                    ->action(function (array $data, CodeHasher $hasher) {
                        $code = \App\Models\RedeemCode::query()
                            ->where('kod_hash', $hasher->hashPlain($data['kod']))
                            ->first();

                        if ($code === null) {
                            Notification::make()->title('Kod bulunamadı')->danger()->send();

                            return;
                        }

                        Notification::make()
                            ->title('Parti #'.$code->batch_id.' · '.$code->durum->label().' · son 4: '.$code->son_dort)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('iptal')
                    ->label('Aktifleri iptal et')
                    ->requiresConfirmation()
                    ->visible(fn (CodeBatch $record) => $record->activeCount() > 0)
                    ->action(function (CodeBatch $record) {
                        $record->codes()->where('durum', CodeStatus::Aktif->value)->update([
                            'durum' => CodeStatus::Iptal->value,
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCodeBatches::route('/'),
        ];
    }
}
