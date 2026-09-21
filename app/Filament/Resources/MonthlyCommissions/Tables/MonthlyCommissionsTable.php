<?php

namespace App\Filament\Resources\MonthlyCommissions\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Services\ReportService;
use App\Support\StaffTransactionCreator;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class MonthlyCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('month', 'desc')
            ->columns([
                TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Month')
                    ->date('M Y')
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%')
                    ->summarize(TableSummaries::percentAverage())
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label('Monthly payment')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Paid against month')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->state(fn (MonthlyCommission $record): float => $record->total_paid),
                TextColumn::make('monthly_remaining')
                    ->label('Monthly remaining')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->state(fn (MonthlyCommission $record): float => $record->monthly_remaining)
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'success'),
                TextColumn::make('carried_forward_from_previous')
                    ->label('Previous balance')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_payable')
                    ->label('Overall payable')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->state(fn (MonthlyCommission $record): float => $record->total_payable),
                TextColumn::make('advances_deducted')
                    ->label('Ledger paid')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_amount')
                    ->label('Manual paid')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance_due')
                    ->label('Overall remaining')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'success'),
                TextColumn::make('balance_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Due' ? 'warning' : 'success'),
                TextColumn::make('cash_collected')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expense_total')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_profit')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('generated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        ->commissioned()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('month_filter')
                    ->label('Month')
                    ->options(self::monthOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereMonth('month', (int) $data['value']);
                    }),
                SelectFilter::make('year')
                    ->label('Year')
                    ->options(fn (): array => self::yearOptions())
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereYear('month', (int) $data['value']);
                    }),
                SelectFilter::make('balance')
                    ->label('Balance')
                    ->options([
                        'due' => 'Due',
                        'paid' => 'Paid / advance',
                    ])
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if (($data['value'] ?? null) === 'due') {
                            $query->where('balance_due', '>', 0);
                        }

                        if (($data['value'] ?? null) === 'paid') {
                            $query->where('balance_due', '<=', 0);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('recordPayout')
                    ->label('Pay')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (MonthlyCommission $record): bool => (float) $record->balance_due > 0)
                    ->modalHeading(fn (MonthlyCommission $record): string => 'Pay '.$record->staff?->name.' commission')
                    ->modalSubmitActionLabel('Record payout')
                    ->form([
                        DatePicker::make('transaction_date')
                            ->label('Payment date')
                            ->default(today())
                            ->required(),
                        Select::make('paid_from')
                            ->label('Paid from')
                            ->options(StaffTransaction::paidFromOptions())
                            ->default('cash')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->minValue(0.01)
                            ->default(fn (MonthlyCommission $record): float => max(0, (float) $record->balance_due))
                            ->required(),
                        TextInput::make('description')
                            ->default('Commission payout'),
                    ])
                    ->action(function (MonthlyCommission $record, array $data): void {
                        StaffTransactionCreator::create([
                            'staff_id' => $record->staff_id,
                            'transaction_date' => $data['transaction_date'],
                            'commission_month' => $record->month?->toDateString(),
                            'type' => 'payout',
                            'paid_from' => $data['paid_from'],
                            'amount' => round((float) $data['amount'], 2),
                            'description' => $data['description'] ?: 'Commission payout',
                        ]);

                        $record->refresh();
                        app(ReportService::class)->generateMonthlyCommission($record->staff, $record->month);
                    })
                    ->successNotificationTitle('Commission payout recorded'),
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
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
        $years = MonthlyCommission::query()
            ->pluck('month')
            ->filter()
            ->map(fn ($month): int => $month instanceof \DateTimeInterface ? (int) $month->format('Y') : (int) date('Y', strtotime((string) $month)))
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year): array => [$year => $year])
            ->all();

        return $years ?: [now()->year => now()->year];
    }
}
