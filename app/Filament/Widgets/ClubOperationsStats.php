<?php

namespace App\Filament\Widgets;

use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ClubOperationsStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected ?string $heading = 'Club operations stats';

    protected int|array|null $columns = [
        '@xl' => 5,
        '!@lg' => 5,
    ];

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $report = app(ReportService::class)->daily($selectedDate);
        $activeSessions = GameSession::query()->active()->count();
        $openBalances = (float) GameParticipant::query()->outstanding()->sum('total_due')
            - (float) GameParticipant::query()->outstanding()->sum('amount_paid');

        return [
            Stat::make('Active tables', $activeSessions)
                ->description('Sessions currently running')
                ->color($activeSessions > 0 ? 'warning' : 'success'),
            Stat::make('Gross sale', $this->money($report['gross_sales_total']))
                ->description($selectedDate->format('d M Y'))
                ->color('success'),
            Stat::make('Customer dues', $this->money($report['dues_added']))
                ->description('New dues for selected day')
                ->color($report['dues_added'] > 0 ? 'danger' : 'success'),
            Stat::make('Expenses', $this->money($report['expense_total']))
                ->description($selectedDate->format('d M Y'))
                ->color($report['expense_total'] > 0 ? 'danger' : 'gray'),
            Stat::make('Total collection', $this->money($report['total_collection']))
                ->description('Actual collection after dues')
                ->color('success'),
            Stat::make('Staff paid', $this->money($report['staff_paid_total']))
                ->description('Staff advances and payouts on selected day')
                ->color($report['staff_paid_total'] > 0 ? 'warning' : 'gray'),
            Stat::make('Salesmen commission', $this->money($report['staff_share_estimate']))
                ->description('Commission based on actual collection')
                ->color('warning'),
            Stat::make('Payment due', $this->money(max(0, $openBalances)))
                ->description('Unpaid or partial checkouts')
                ->color($openBalances > 0 ? 'danger' : 'success'),
            Stat::make('Frames', number_format((int) $report['frames_count']))
                ->description('Solo and doubles frames')
                ->color('info'),
            Stat::make('Add-ons', $this->money($report['add_on_total']))
                ->description('Items recorded separately')
                ->color('primary'),
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
