<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CapitalLiabilityPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('payment_date', 'desc')
            ->columns([
                TextColumn::make('capitalLiability.title')
                    ->label('Loan / item')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capitalLiability.lender_name')
                    ->label('Bank / friend')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('paid_from_label')
                    ->label('Paid from')
                    ->badge(),
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
