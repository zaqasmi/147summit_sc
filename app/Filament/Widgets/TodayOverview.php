<?php

namespace App\Filament\Widgets;

use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class TodayOverview extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Sales and profit';

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $reports = app(ReportService::class);
        $report = $reports->daily($selectedDate);
        $activeSessions = GameSession::query()->active()->count();
        $openBalances = (float) GameParticipant::query()->outstanding()->sum('total_due')
            - (float) GameParticipant::query()->outstanding()->sum('amount_paid');

        if (auth()->user()?->isAdmin()) {
            $business = $reports->businessSummary($selectedDate);
            $label = 'Till '.$selectedDate->format('d M Y');

            return [
                Stat::make('Gross sale', $this->money($business['gross_sales_total']))
                    ->description($label)
                    ->color('success'),
                Stat::make('Customer dues', $this->money($business['dues_added']))
                    ->description('New dues till selected date')
                    ->color($business['dues_added'] > 0 ? 'danger' : 'success'),
                Stat::make('Expense', $this->money($business['expense_total']))
                    ->description($label)
                    ->color($business['expense_total'] > 0 ? 'danger' : 'gray'),
                Stat::make('Total collection', $this->money($business['total_collection']))
                    ->description('Actual collection after dues')
                    ->color('success'),
                Stat::make('Staff commission', $this->money($business['staff_commission']))
                    ->description('Calculated from profit')
                    ->color('warning'),
                Stat::make('My profit', $this->money($business['my_profit']))
                    ->description('After expenses and staff commission')
                    ->color($business['my_profit'] >= 0 ? 'success' : 'danger'),
            ];
        }

        $stats = [
            Stat::make('Active tables', $activeSessions)
                ->description('Sessions currently running')
                ->color($activeSessions > 0 ? 'warning' : 'success'),
            Stat::make('Cash collected', $this->money($report['cash_collected']))
                ->description($selectedDate->format('d M Y'))
                ->color('success'),
            Stat::make('Expenses', $this->money($report['expense_total']))
                ->description($selectedDate->format('d M Y'))
                ->color($report['expense_total'] > 0 ? 'danger' : 'gray'),
        ];

        if (auth()->user()?->isAdmin()) {
            $stats[] = Stat::make('Capital left to recover', $this->money($report['capital']['capital_remaining']))
                ->description('After owner profit and staff share')
                ->color($report['capital']['capital_remaining'] > 0 ? 'warning' : 'success');
        } else {
            $stats[] = Stat::make('Open player balances', $this->money(max(0, $openBalances)))
                ->description('Unpaid or partial checkouts')
                ->color($openBalances > 0 ? 'danger' : 'success');
        }

        return $stats;
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
