<?php

namespace App\Filament\Resources\OwnerCapitals\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OwnerCapitalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('entry_date', 'desc')
            ->columns([
                TextColumn::make('entry_date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($record): string => $record->type === 'capital_reduction' ? 'warning' : 'success'),
                TextColumn::make('source_label')
                    ->label('Source')
                    ->badge()
                    ->color(fn ($record): string => $record->source_type ? 'info' : 'gray'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('signed_amount')
                    ->label('Capital effect')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::capitalEffectTotal()),
                TextColumn::make('description')
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

    private static function capitalEffectTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn ($query): float => (float) (clone $query)
                ->get(['type', 'amount'])
                ->sum(fn ($row): float => $row->type === 'capital_reduction'
                    ? -1 * (float) $row->amount
                    : (float) $row->amount))
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }
}
