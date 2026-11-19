<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ChapterUnlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'chapterUnlocks';

    protected static ?string $title = 'Açılan bölümler';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('chapter.series'))
            ->columns([
                Tables\Columns\TextColumn::make('chapter.series.title')->label('Seri'),
                Tables\Columns\TextColumn::make('chapter.number')->label('Bölüm'),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
            ]);
    }
}
