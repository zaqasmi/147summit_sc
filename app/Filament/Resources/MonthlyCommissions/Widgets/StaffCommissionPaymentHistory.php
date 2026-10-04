<?php

namespace App\Filament\Resources\MonthlyCommissions\Widgets;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Models\Staff;
use App\Models\StaffCommissionPayment;
use App\Models\StaffTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class StaffCommissionPaymentHistory extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return MonthlyCommissionResource::canViewAny();
    }

    #[On('cash-bank-balances-updated')]
    public function refreshPayments(): void
    {
        $this->flushCachedTableRecords();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Commission staff payment history')
            ->description('Advances and payouts grouped by commission month, plus the saved payment total from each monthly closing. Balances are the latest recorded totals for that staff member and month, not the balance after each payment. Use the filters on this table to choose staff or month.')
            ->query(fn (): Builder => StaffCommissionPayment::recordsQuery())
            ->queryStringIdentifier('commissionPayments')
            ->defaultSort('payment_date', 'desc')
            ->groups([
                Group::make('commission_month')
                    ->label('Commission month')
                    ->getTitleFromRecordUsing(fn (StaffCommissionPayment $record): string => Carbon::parse($record->commission_month)->format('F Y')),
            ])
            ->defaultGroup('commission_month')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('staff.name')->label('Staff')->searchable()->sortable(),
                TextColumn::make('commission_month')->label('Commission month')->date('M Y')->sortable(),
                TextColumn::make('payment_date')->label('Payment date')->date()->placeholder('Not recorded')->sortable(),
                TextColumn::make('type')->label('Payment type')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'advance' => 'Advance',
                    'payout' => 'Payout',
                    'closing_payment' => 'Monthly closing',
                    default => $state,
                }),
                TextColumn::make('paid_from')->label('Paid from')
                    ->formatStateUsing(fn (string $state): string => StaffTransaction::paidFromOptions()[$state] ?? ucfirst(str_replace('_', ' ', $state)))
                    ->placeholder('Not recorded'),
                TextColumn::make('amount')->label('Payment amount')->money('PKR')->sortable(),
                TextColumn::make('previous_balance')->label('Previous balance')->money('PKR')->placeholder('Not generated')->toggleable(),
                TextColumn::make('commission_amount')->label('Month commission')->money('PKR')->placeholder('Not generated'),
                TextColumn::make('total_paid')->label('Total paid for month')->money('PKR')->placeholder('Not generated'),
                TextColumn::make('remaining_due')->label('Month remaining due')->money('PKR')
                    ->state(fn (StaffCommissionPayment $record): ?float => $record->balance_due === null ? null : max(0, (float) $record->balance_due))
                    ->placeholder('Not generated')->color('warning'),
                TextColumn::make('advance_carried')->label('Advance carried forward')->money('PKR')
                    ->state(fn (StaffCommissionPayment $record): ?float => $record->balance_due === null ? null : max(0, -(float) $record->balance_due))
                    ->placeholder('Not generated')->color('success'),
                TextColumn::make('description')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('staff_id')->label('Staff')
                    ->options(fn (): array => Staff::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                SelectFilter::make('commission_month')->label('Commission month')
                    ->options(fn (): array => StaffCommissionPayment::recordsQuery()->reorder()->distinct()->pluck('commission_month')->sortDesc()
                        ->mapWithKeys(fn (string $month): array => [$month => Carbon::parse($month)->format('F Y')])->all()),
                SelectFilter::make('type')->label('Payment type')->options([
                    'advance' => 'Advance', 'payout' => 'Payout', 'closing_payment' => 'Monthly closing',
                ]),
                SelectFilter::make('paid_from')->label('Paid from')->options(StaffTransaction::paidFromOptions()),
            ])
            ->emptyStateHeading('No commission payments recorded');
    }
}
