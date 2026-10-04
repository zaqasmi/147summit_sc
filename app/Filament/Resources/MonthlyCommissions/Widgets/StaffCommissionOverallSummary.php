<?php

namespace App\Filament\Resources\MonthlyCommissions\Widgets;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Models\MonthlyCommission;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Collection;

class StaffCommissionOverallSummary extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Overall staff commission';

    protected ?string $description = 'Earned and paid totals follow the balance table filters. Remaining dues and advance credit use each staff member’s latest selected month so carried balances are counted once.';

    protected ?string $pollingInterval = null;

    private ?Collection $commissionRecords = null;

    public static function canView(): bool
    {
        return MonthlyCommissionResource::canViewAny();
    }

    public function content(Schema $schema): Schema
    {
        $staffStats = $this->staffRemainingStats();

        return $schema->components([
            $this->getSectionContentComponent(),
            Section::make('Staff-wise remaining commission')
                ->description('One balance for each staff member at their latest selected month. Overpayments appear as advance credit.')
                ->schema($staffStats)
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->contained(false)
                ->gridContainer()
                ->visible($staffStats !== []),
        ]);
    }

    private function staffRemainingStats(): array
    {
        $records = $this->records();

        return $records->sortByDesc('month')->unique('staff_id')
            ->sortBy(fn (MonthlyCommission $record): string => $record->staff?->name ?? '')
            ->map(function (MonthlyCommission $record) use ($records): Stat {
                $balance = (float) $record->balance_due;
                $paid = (float) $records->where('staff_id', $record->staff_id)
                    ->sum(fn (MonthlyCommission $month): float => $month->total_paid);
                $name = $record->staff?->name ?? 'Staff #'.$record->staff_id;

                return Stat::make($name.($balance < 0 ? ' — advance credit' : ' — remaining due'), $this->money(abs($balance)))
                    ->key('staff-remaining-'.$record->staff_id)
                    ->description($record->month->format('M Y').' · Paid '.$this->money($paid).' in selected months')
                    ->color($balance > 0 ? 'warning' : ($balance < 0 ? 'info' : 'success'));
            })->values()->all();
    }

    protected function getTablePage(): string
    {
        return ListMonthlyCommissions::class;
    }

    protected function getStats(): array
    {
        $records = $this->records();
        $commissionEarned = $this->sum($records, 'commission_amount');
        $ledgerPaid = $this->sum($records, 'advances_deducted');
        $manualPaid = $this->sum($records, 'paid_amount');
        $totalPaid = round($ledgerPaid + $manualPaid, 2);
        $monthlyRemaining = round($commissionEarned - $totalPaid, 2);
        $latestBalances = $records->sortByDesc('month')->unique('staff_id');
        $previousBalance = $this->sum($latestBalances, 'carried_forward_from_previous');
        $remainingDue = round((float) $latestBalances->sum(fn (MonthlyCommission $record): float => max(0, (float) $record->balance_due)), 2);
        $advanceCredit = round((float) $latestBalances->sum(fn (MonthlyCommission $record): float => max(0, -(float) $record->balance_due)), 2);

        return [
            Stat::make('Records', number_format($records->count()))
                ->description('Filtered staff-month rows')
                ->color('gray'),
            Stat::make('Commission earned', $this->money($commissionEarned))
                ->description('Total monthly commission')
                ->color('info'),
            Stat::make('Total commission paid to staff', $this->money($totalPaid))
                ->description('Advances + payouts + closing payments')
                ->color($totalPaid > 0 ? 'success' : 'gray'),
            Stat::make('Monthly remaining', $this->money($monthlyRemaining))
                ->description('Earned minus paid in filtered months')
                ->color($monthlyRemaining > 0 ? 'warning' : 'success'),
            Stat::make('Previous balance', $this->money($previousBalance))
                ->description('Before each staff member’s latest selected month')
                ->color($previousBalance > 0 ? 'warning' : 'gray'),
            Stat::make('Total remaining to pay staff', $this->money($remainingDue))
                ->description('Unpaid dues at each staff member’s latest selected month')
                ->color($remainingDue > 0 ? 'warning' : 'success'),
            Stat::make('Advance credit carried forward', $this->money($advanceCredit))
                ->description('Overpayments shown separately from other staff dues')
                ->color($advanceCredit > 0 ? 'info' : 'gray'),
        ];
    }

    /**
     * @return Collection<int, MonthlyCommission>
     */
    private function records(): Collection
    {
        return $this->commissionRecords ??= $this->getPageTableQuery()
            ->with('staff:id,name')
            ->get([
                'id',
                'staff_id',
                'month',
                'commission_amount',
                'advances_deducted',
                'paid_amount',
                'carried_forward_from_previous',
                'balance_due',
            ]);
    }

    /**
     * @param  Collection<int, MonthlyCommission>  $records
     */
    private function sum(Collection $records, string $column): float
    {
        return round((float) $records->sum(fn (MonthlyCommission $record): float => (float) $record->{$column}), 2);
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
