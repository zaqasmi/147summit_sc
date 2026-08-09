<?php

namespace App\Filament\Resources\CustomerDues\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerDuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opening_balance')
                    ->label('Opening')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_charged')
                    ->label('Dues added')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance due')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->summarize(self::moneyTotal())
                    ->sortable(),
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

    private static function moneyTotal(): Sum
    {
        return Sum::make()
            ->label('Total')
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
