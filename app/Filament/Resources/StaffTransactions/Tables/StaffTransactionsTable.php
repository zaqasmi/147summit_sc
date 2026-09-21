<?php

namespace App\Filament\Resources\StaffTransactions\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\Staff;
use App\Models\StaffTransaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class StaffTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable(),
                TextColumn::make('cashDeposit.deposit_date')
                    ->label('Daily closing')
                    ->date()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('Payment date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('commission_month')
                    ->label('Commission month')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('paid_from_label')
                    ->label('Paid from')
                    ->badge()
                    ->color(fn (StaffTransaction $record): string => $record->paid_from === 'cash' ? 'warning' : 'success'),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
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
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('staff_id')
                    ->label('Staff')
                    ->options(fn (): array => Staff::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('type')
                    ->options([
                        'advance' => 'Advance paid',
                        'payout' => 'Commission payout',
                        'adjustment' => 'Adjustment',
                    ]),
                SelectFilter::make('paid_from')
                    ->label('Paid from')
                    ->options(StaffTransaction::paidFromOptions()),
                SelectFilter::make('commission_month_filter')
                    ->label('Commission month')
                    ->options(self::monthOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereMonth('commission_month', (int) $data['value']);
                    }),
                SelectFilter::make('commission_year')
                    ->label('Commission year')
                    ->options(fn (): array => self::yearOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereYear('commission_month', (int) $data['value']);
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

    /**
     * @return array<int, string>
     */
    private static function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [$month => now()->month($month)->format('F')])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private static function yearOptions(): array
    {
        $years = StaffTransaction::query()
            ->pluck('commission_month')
            ->filter()
            ->map(fn ($month): int => $month instanceof \DateTimeInterface ? (int) $month->format('Y') : (int) date('Y', strtotime((string) $month)))
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year): array => [$year => $year])
            ->all();

        return $years ?: [now()->year => now()->year];
    }
}
