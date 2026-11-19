<?php

namespace App\Filament\Resources\SeriesResource\RelationManagers;

use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChaptersRelationManager extends RelationManager
{
    protected static string $relationship = 'chapters';

    protected static ?string $title = 'Bölümler';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')
                ->label('Numara')
                ->numeric()
                ->required()
                ->step(0.01),
            Forms\Components\TextInput::make('title')->label('Başlık')->maxLength(255),
            Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi'),
            Forms\Components\TextInput::make('ruh_tasi_fiyat')
                ->label('Ruh taşı fiyatı')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('vip_ucretsiz')->label('VIP ücretsiz'),
            Forms\Components\DateTimePicker::make('erken_erisim_bitis')->label('Erken erişim bitiş'),
            Forms\Components\FileUpload::make('zip')
                ->label('Sayfa ZIP')
                ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                ->maxSize((int) config('content.max_zip_kilobytes'))
                ->disk('local')
                ->directory('chapter-zips')
                ->dehydrated(false)
                ->helperText('Görseller doğal sırayla (1, 2, 10) işlenir, WebP olarak saklanır. Hatalı dosya işlemi durdurmaz.'),
            Forms\Components\Checkbox::make('adult_characters_confirmed')
                ->label('Tüm karakterler yetişkindir; reşit olmayan karakter içeren içerik yasaktır')
                ->accepted()
                ->dehydrated(false)
                ->visible(fn (?Chapter $record) => $record === null),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('No')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Başlık'),
                Tables\Columns\TextColumn::make('pages_count')->counts('pages')->label('Sayfa'),
                Tables\Columns\TextColumn::make('ruh_tasi_fiyat')->label('Ruh taşı'),
                Tables\Columns\IconColumn::make('vip_ucretsiz')->label('VIP')->boolean(),
                Tables\Columns\TextColumn::make('uploads.status')
                    ->label('Yükleme')
                    ->state(fn (Chapter $record) => $record->uploads()->latest()->first()?->status),
                Tables\Columns\TextColumn::make('ilerleme')
                    ->label('İlerleme')
                    ->state(function (Chapter $record): string {
                        $upload = $record->uploads()->latest()->first();

                        return $upload ? $upload->progressPercent().'%' : '—';
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(fn (Chapter $record, array $data, Tables\Actions\CreateAction $action) => $this->queueZip($record)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn (Chapter $record) => $this->queueZip($record)),
                Tables\Actions\Action::make('hatalar')
                    ->label('Hatalar')
                    ->visible(fn (Chapter $record) => filled($record->uploads()->latest()->first()?->failures))
                    ->modalContent(fn (Chapter $record) => view('filament.content.upload-failures', [
                        'failures' => $record->uploads()->latest()->first()?->failures ?? [],
                    ])),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    private function queueZip(Chapter $chapter): void
    {
        $state = $this->mountedTableActionsData[0]['zip'] ?? null;
        $path = is_array($state) ? ($state[0] ?? null) : $state;

        if (! is_string($path) || $path === '') {
            return;
        }

        ProcessChapterZip::dispatch($chapter->id, 'local', $path, auth()->id());
    }
}
