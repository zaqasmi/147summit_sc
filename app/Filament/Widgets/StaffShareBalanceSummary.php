<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StaffShareBalanceSummary extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Staff percentage balance';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $summary = app(ReportService::class)->staffShareSummary($selectedDate, includeScheduledRent: true);
        $balance = (float) $summary['balance_due_total'];
        $balanceDescription = $balance >= 0
            ? 'Remaining commission to pay till '.$selectedDate->format('d M Y')
            : 'Advance overpaid; carry forward from '.$selectedDate->format('d M Y');

        return [
            Stat::make('Staff percentage earned', $this->money($summary['share_earned_total']))
                ->description('Based on profit after expenses')
                ->color('info'),
            Stat::make('Amount paid to staff', $this->money($summary['amount_paid_total']))
                ->description('Advances + payouts')
                ->color('success'),
            Stat::make('Staff balance left', $this->money($balance))
                ->description($balanceDescription)
                ->color($balance > 0 ? 'warning' : 'info'),
        ];
    }

    private function selectedDate(): Carbon
    {
        $date = $this->pageFilters['date'] ?? null;

        try {
            return Carbon::parse($date ?: today())->startOfDay();
        } catch (\Throwable) {
            return today();
        }
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
