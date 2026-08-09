<?php

namespace App\Filament\Resources\BankTransactions\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\BankTransaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('transaction_date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('entry_side_label')
                    ->label('Debit/Credit')
                    ->badge()
                    ->color(fn (BankTransaction $record): string => $record->entry_side === 'credit' ? 'success' : 'danger'),
                TextColumn::make('type_label')
                    ->label('Type')
                    ->badge()
                    ->color(fn (BankTransaction $record): string => BankTransaction::typeColor($record->type)),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('signed_amount')
                    ->label('Bank effect')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::balanceEffectTotal()),
                TextColumn::make('source_label')
                    ->label('Source'),
                TextColumn::make('description')
                    ->searchable()
                    ->limit(40),
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

    private static function balanceEffectTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Balance')
            ->using(fn ($query): float => (float) (clone $query)
                ->get(['type', 'amount'])
                ->sum(fn ($transaction): float => self::signedAmount(
                    (string) $transaction->type,
                    (float) $transaction->amount,
                )))
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }

    private static function signedAmount(string $type, float $amount): float
    {
        return BankTransaction::entrySideForType($type) === 'debit'
            ? -1 * $amount
            : $amount;
    }
}
