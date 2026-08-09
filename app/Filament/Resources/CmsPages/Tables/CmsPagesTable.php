<?php

namespace App\Filament\Resources\CmsPages\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\CmsPage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CmsPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('title')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('section')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CmsPage::sectionOptions()[$state] ?? ucfirst($state))
                    ->searchable(),
                TextColumn::make('meta_title')
                    ->label('SEO title')
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
                SelectFilter::make('section')
                    ->options(CmsPage::sectionOptions()),
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
