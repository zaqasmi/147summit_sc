<?php

namespace App\Filament\Resources\CashDeposits\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\CashDeposit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

class CashDepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('deposit_date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('closing_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'game_sessions', 'system' => 'Game sessions',
                        default => 'Manual',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'game_sessions', 'system' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('manual_table_1_sale')
                    ->label('Table 1 sale')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('manual_table_2_sale')
                    ->label('Table 2 sale')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('manual_table_3_sale')
                    ->label('Table 3 sale')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('manual_table_4_sale')
                    ->label('Table 4 sale')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('manual_sales_total')
                    ->label('Total sale')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::registerSalesTotal()),
                TextColumn::make('manual_expense_total')
                    ->label('Expense')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color('warning')
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('dues_added')
                    ->label('Customer dues')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color('danger')
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('dues_recovered')
                    ->label('Dues recovered')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color('success')
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('petty_cash_kept')
                    ->label('Petty cash in counter')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('cash_to_be_collected')
                    ->label('Cash to be collected')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::cashToBeCollectedTotal()),
                TextColumn::make('amount_collected_from_staff')
                    ->label('Actual daily collection')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color('success')
                    ->summarize(self::moneyTotal())
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
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('month')
                    ->label('Month')
                    ->options(self::monthOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereMonth('deposit_date', (int) $data['value']);
                    }),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(fn (): array => self::yearOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereYear('deposit_date', (int) $data['value']);
                    }),
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

    private static function registerSalesTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn (Builder $query): float => self::sumColumn($query, 'manual_table_1_sale')
                + self::sumColumn($query, 'manual_table_2_sale')
                + self::sumColumn($query, 'manual_table_3_sale')
                + self::sumColumn($query, 'manual_table_4_sale'))
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    private static function cashToBeCollectedTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn (Builder $query): float => max(
                0,
                self::sumColumn($query, 'cash_collected_from_counter')
                    - self::sumColumn($query, 'petty_cash_kept'),
            ))
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    /**
     * @return array<int, string>
     */
    private static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => Carbon::create(2000, $month, 1)->format('F'),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private static function yearOptions(): array
    {
        return CashDeposit::query()
            ->whereNotNull('deposit_date')
            ->orderByDesc('deposit_date')
            ->pluck('deposit_date')
            ->map(fn ($date): int => Carbon::parse($date)->year)
            ->unique()
            ->mapWithKeys(fn (int $year): array => [$year => $year])
            ->all();
    }

    private static function sumColumn(Builder $query, string $column): float
    {
        return (float) (clone $query)->sum($column);
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
