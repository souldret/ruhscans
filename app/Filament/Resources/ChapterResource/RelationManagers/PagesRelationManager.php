<?php

namespace App\Filament\Resources\ChapterResource\RelationManagers;

use App\Models\Page;
use App\Services\Reader\PageUrlFactory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'Sayfalar';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('order')->label('Sıra')->numeric()->required(),
            Forms\Components\FileUpload::make('replacement')
                ->label('Görsel')
                ->image()
                ->disk(config('content.image_disk'))
                ->directory(fn () => 'chapters/'.$this->getOwnerRecord()->series_id.'/'.$this->getOwnerRecord()->id)
                ->visibility('private')
                ->required(fn (?Page $record) => $record === null)
                ->dehydrated(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\ImageColumn::make('thumb')
                    ->label('Sayfa')
                    ->height(72)
                    ->getStateUsing(function (Page $record) {
                        try {
                            return app(PageUrlFactory::class)->temporary($record);
                        } catch (\Throwable) {
                            return null;
                        }
                    }),
                Tables\Columns\TextColumn::make('order')->label('Sıra')->sortable(),
                Tables\Columns\TextColumn::make('width')->label('En'),
                Tables\Columns\TextColumn::make('height')->label('Boy'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Sayfa ekle')
                    ->using(function (array $data) {
                        $path = $this->storedImage();
                        $order = (int) ($data['order'] ?? (((int) $this->getOwnerRecord()->pages()->max('order')) + 1));

                        return $this->getRelationship()->create([
                            'order' => $order,
                            'r2_path' => $path ?? '',
                            'width' => null,
                            'height' => null,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Değiştir')
                    ->using(function (Page $record, array $data) {
                        $path = $this->storedImage();

                        if (is_string($path) && $path !== '') {
                            Storage::disk((string) config('content.image_disk'))->delete($record->r2_path);
                            $record->r2_path = $path;
                        }

                        $record->order = (int) $data['order'];
                        $record->save();

                        return $record;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(fn (Page $record) => Storage::disk((string) config('content.image_disk'))->delete($record->r2_path)),
            ])
            ->bulkActions([]);
    }

    private function storedImage(): ?string
    {
        $state = $this->mountedTableActionsData[0]['replacement'] ?? null;
        $path = is_array($state) ? (array_values($state)[0] ?? null) : $state;

        return is_string($path) && $path !== '' ? $path : null;
    }
}
