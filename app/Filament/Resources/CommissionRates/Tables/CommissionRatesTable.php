<?php

namespace App\Filament\Resources\CommissionRates\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('effective_from', 'desc')
            ->columns([
                TextColumn::make('effective_from')
                    ->label('Effective from')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('rate')
                    ->label('Overall commission rate')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%')
                    ->summarize(TableSummaries::percentAverage())
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->searchable(),
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
                //
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
