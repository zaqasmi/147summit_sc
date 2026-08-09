<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class LoanSummary extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Loans';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $liabilities = app(ReportService::class)->liabilitySummary($selectedDate);
        $label = 'Till ' . $selectedDate->format('d M Y');

        return [
            Stat::make('Loan total', $this->money($liabilities['loan_principal_total']))
                ->description('Bank/friend loan')
                ->color('gray'),
            Stat::make('Total loan paid', $this->money($liabilities['loan_paid_to_date']))
                ->description($label)
                ->color('success'),
            Stat::make('Loan left', $this->money($liabilities['loan_balance_total']))
                ->description('Remaining loan balance')
                ->color($liabilities['loan_balance_total'] > 0 ? 'danger' : 'success'),
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
        return 'Rs ' . number_format((float) $amount, 2);
    }
}
