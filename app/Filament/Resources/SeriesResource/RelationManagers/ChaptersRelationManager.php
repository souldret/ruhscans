<?php

namespace App\Filament\Resources\SeriesResource\RelationManagers;

use App\Filament\Support\UploadFields;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\Series;
use App\Services\Content\BulkChapterZipImporter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ChaptersRelationManager extends RelationManager
{
    protected static string $relationship = 'chapters';

    protected static ?string $title = 'Bölümler';

    public function form(Form $form): Form
    {
        return $form->schema(self::fields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->modifyQueryUsing(fn ($query) => $query->with('latestUpload')->withCount('pages'))
            ->columns(self::columns())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Bölüm ekle')
                    ->after(fn (Chapter $record) => $this->queueZip($record)),
                Tables\Actions\Action::make('toplu')
                    ->label('Toplu bölüm yükle')
                    ->icon('heroicon-o-archive-box')
                    ->form([
                        UploadFields::zip('bulk_zip', 'Bölüm klasörleri ZIP')
                            ->required()
                            ->helperText('Kökte 1/, 2/, 3/ gibi klasörler olsun. Her klasör bir bölüm olur. '.UploadFields::zip('x', 'x')->getHelperText()),
                        Forms\Components\TextInput::make('ruh_tasi_fiyat')->label('Ruh taşı fiyatı')->numeric()->minValue(0)->default(0)->required(),
                        Forms\Components\Toggle::make('vip_ucretsiz')->label('VIP ücretsiz'),
                        Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi')->default(now()),
                        Forms\Components\DateTimePicker::make('erken_erisim_bitis')->label('Erken erişim bitiş'),
                        UploadFields::adultConsent(),
                    ])
                    ->action(function (array $data, BulkChapterZipImporter $importer) {
                        $path = self::uploadedPath($data['bulk_zip'] ?? null);

                        if ($path === null) {
                            Notification::make()->title('ZIP seçilmedi')->danger()->send();

                            return;
                        }

                        /** @var Series $series */
                        $series = $this->getOwnerRecord();
                        $ids = $importer->queue(
                            $series,
                            Storage::disk('local')->path($path),
                            $data,
                            auth()->id(),
                        );

                        Storage::disk('local')->delete($path);

                        Notification::make()->title(count($ids).' bölüm kuyruğa alındı')->success()->send();
                    }),
            ])
            ->actions(self::rowActions(fn (Chapter $record) => $this->queueZip($record)))
            ->bulkActions(self::bulk());
    }

    /**
     * @return list<Forms\Components\Component>
     */
    public static function fields(bool $withSeries = false): array
    {
        $fields = [];

        if ($withSeries) {
            $fields[] = Forms\Components\Select::make('series_id')
                ->label('Seri')
                ->relationship('series', 'title')
                ->searchable()
                ->preload()
                ->required();
        }

        return [
            ...$fields,
            Forms\Components\TextInput::make('number')->label('Numara')->numeric()->step('0.01')->required(),
            Forms\Components\TextInput::make('title')->label('Başlık')->maxLength(255),
            Forms\Components\TextInput::make('ruh_tasi_fiyat')->label('Ruh taşı fiyatı')->numeric()->minValue(0)->default(0)->required(),
            Forms\Components\Toggle::make('vip_ucretsiz')->label('VIP ücretsiz'),
            Forms\Components\DateTimePicker::make('erken_erisim_bitis')->label('Erken erişim bitiş'),
            Forms\Components\DateTimePicker::make('published_at')->label('Zamanlanmış yayın'),
            UploadFields::zip('zip', 'Sayfa ZIP')->dehydrated(false),
            UploadFields::adultConsent()->visible(fn (?Chapter $record) => $record === null),
        ];
    }

    /**
     * @return list<Tables\Columns\Column>
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('number')->label('No')->sortable(),
            Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable(),
            Tables\Columns\TextColumn::make('pages_count')->counts('pages')->label('Sayfa'),
            Tables\Columns\TextColumn::make('ruh_tasi_fiyat')->label('Ruh taşı'),
            Tables\Columns\IconColumn::make('vip_ucretsiz')->label('VIP')->boolean(),
            Tables\Columns\TextColumn::make('latestUpload.status')
                ->label('Yükleme')
                ->badge()
                ->formatStateUsing(fn (?string $state) => match ($state) {
                    'isleniyor' => 'İşleniyor',
                    'tamamlandi' => 'Hazır',
                    'kismi' => 'Hata',
                    'hata' => 'Hata',
                    default => $state ? $state : '—',
                })
                ->color(fn (?string $state) => match ($state) {
                    'isleniyor' => 'warning',
                    'tamamlandi' => 'success',
                    'kismi', 'hata' => 'danger',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('ilerleme')
                ->label('İlerleme')
                ->state(fn (Chapter $record) => $record->latestUpload ? $record->latestUpload->progressPercent().'%' : '—'),
            Tables\Columns\TextColumn::make('hata')
                ->label('Hata')
                ->state(function (Chapter $record): string {
                    $failures = $record->latestUpload?->failures ?? [];

                    return $failures === [] ? '—' : (string) ($failures[0]['message'] ?? 'Hata');
                })
                ->limit(40),
        ];
    }

    /**
     * @return list<Tables\Actions\Action>
     */
    public static function rowActions(callable $afterEdit): array
    {
        return [
            Tables\Actions\EditAction::make()->after($afterEdit),
            Tables\Actions\Action::make('yeniden')
                ->label('Yeniden dene')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Chapter $record) => in_array($record->latestUpload?->status, ['kismi', 'hata'], true))
                ->form([
                    UploadFields::zip('zip', 'Sayfa ZIP')->required(),
                    UploadFields::adultConsent(),
                ])
                ->action(function (Chapter $record, array $data) {
                    $path = self::uploadedPath($data['zip'] ?? null);

                    if ($path === null) {
                        return;
                    }

                    ProcessChapterZip::dispatch($record->id, 'local', $path, auth()->id());
                    Notification::make()->title('Yeniden kuyruğa alındı')->success()->send();
                }),
            Tables\Actions\DeleteAction::make(),
        ];
    }

    public static function bulk(): Tables\Actions\BulkActionGroup
    {
        return Tables\Actions\BulkActionGroup::make([
            Tables\Actions\BulkAction::make('fiyat')
                ->label('Fiyat güncelle')
                ->form([
                    Forms\Components\TextInput::make('ruh_tasi_fiyat')->label('Ruh taşı fiyatı')->numeric()->minValue(0)->required(),
                ])
                ->action(fn (Collection $records, array $data) => $records->each->update(['ruh_tasi_fiyat' => (int) $data['ruh_tasi_fiyat']])),
            Tables\Actions\BulkAction::make('vip')
                ->label('VIP güncelle')
                ->form([
                    Forms\Components\Toggle::make('vip_ucretsiz')->label('VIP ücretsiz'),
                ])
                ->action(fn (Collection $records, array $data) => $records->each->update(['vip_ucretsiz' => (bool) ($data['vip_ucretsiz'] ?? false)])),
            Tables\Actions\BulkAction::make('yayin')
                ->label('Yayın durumu')
                ->form([
                    Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi')->helperText('Boşsa yayından kalkar.'),
                ])
                ->action(fn (Collection $records, array $data) => $records->each->update(['published_at' => $data['published_at'] ?? null])),
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function uploadedPath(mixed $state): ?string
    {
        $path = is_array($state) ? (array_values($state)[0] ?? null) : $state;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private function queueZip(Chapter $chapter): void
    {
        $path = self::uploadedPath($this->mountedTableActionsData[0]['zip'] ?? null);

        if ($path === null) {
            return;
        }

        ProcessChapterZip::dispatch($chapter->id, 'local', $path, auth()->id());
    }
}
