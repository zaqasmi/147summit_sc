<?php

namespace App\Filament\Resources\GalleryItems\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\GalleryItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GalleryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('tournament.name')
                    ->label('Tournament')
                    ->searchable(),
                TextColumn::make('title')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => GalleryItem::typeOptions()[$state] ?? ucfirst($state))
                    ->searchable(),
                TextColumn::make('album')
                    ->searchable(),
                ImageColumn::make('file_path')
                    ->label('Image')
                    ->disk('public'),
                TextColumn::make('video_url')
                    ->label('Video')
                    ->searchable(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tournament_id')
                    ->label('Tournament')
                    ->relationship('tournament', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->options(GalleryItem::typeOptions()),
                SelectFilter::make('is_published')
                    ->label('Published')
                    ->options([
                        1 => 'Published',
                        0 => 'Draft',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
