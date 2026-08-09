<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinanceSummary extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Capital investment';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $reports = app(ReportService::class);
        $capital = $reports->capitalSummary($selectedDate);
        $label = 'Till '.$selectedDate->format('d M Y');

        return [
            Stat::make('Capital investment', $this->money($capital['capital_invested']))
                ->description('Net owner capital to cover')
                ->color('info'),
            Stat::make('Capital added', $this->money($capital['capital_added']))
                ->description('Manual and owner-paid liability capital')
                ->color('info'),
            Stat::make('Recovery income', $this->money($capital['capital_reduced']))
                ->description('Separate recovery entries recorded')
                ->color($capital['capital_reduced'] > 0 ? 'success' : 'gray'),
            Stat::make('Profit covered', $this->money($capital['capital_recovered']))
                ->description($label)
                ->color($capital['capital_recovered'] > 0 ? 'success' : 'gray'),
            Stat::make('Remaining capital investment', $this->money($capital['capital_remaining']))
                ->description('Still to be covered')
                ->color($capital['capital_remaining'] > 0 ? 'warning' : 'success'),
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
