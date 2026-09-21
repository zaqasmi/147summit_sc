<?php

namespace App\Filament\Resources\MonthlyCommissions\Widgets;

use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Models\MonthlyCommission;
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

    protected ?string $description = 'Totals for the filtered staff commission report. The table below bifurcates these amounts staff-wise.';

    protected ?string $pollingInterval = null;

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
        $previousBalance = $this->sum($records, 'carried_forward_from_previous');
        $overallRemaining = $this->sum($records, 'balance_due');

        return [
            Stat::make('Records', number_format($records->count()))
                ->description('Filtered staff-month rows')
                ->color('gray'),
            Stat::make('Commission earned', $this->money($commissionEarned))
                ->description('Total monthly commission')
                ->color('info'),
            Stat::make('Paid against month', $this->money($totalPaid))
                ->description('Advances + payouts')
                ->color($totalPaid > 0 ? 'success' : 'gray'),
            Stat::make('Monthly remaining', $this->money($monthlyRemaining))
                ->description('Commission earned - paid')
                ->color($monthlyRemaining > 0 ? 'warning' : 'success'),
            Stat::make('Previous balance', $this->money($previousBalance))
                ->description('Balance brought forward')
                ->color($previousBalance > 0 ? 'warning' : 'gray'),
            Stat::make('Overall remaining', $this->money($overallRemaining))
                ->description('Previous balance + earned - paid')
                ->color($overallRemaining > 0 ? 'warning' : 'success'),
        ];
    }

    /**
     * @return Collection<int, MonthlyCommission>
     */
    private function records(): Collection
    {
        return $this->getPageTableQuery()
            ->get([
                'id',
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
