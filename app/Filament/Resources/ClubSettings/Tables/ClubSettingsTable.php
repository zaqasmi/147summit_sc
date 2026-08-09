<?php

namespace App\Filament\Resources\ClubSettings\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\ClubSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClubSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('key')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('group')
                    ->badge()
                    ->searchable(),
                TextColumn::make('type')
                    ->formatStateUsing(fn (string $state): string => ClubSetting::typeOptions()[$state] ?? ucfirst($state))
                    ->searchable(),
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
                SelectFilter::make('type')
                    ->options(ClubSetting::typeOptions()),
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
