<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class EquipmentInstallmentSummary extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Equipment installments';

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
            Stat::make('Equipment total', $this->money($liabilities['equipment_principal_total']))
                ->description('Solar, ACs, equipment, etc.')
                ->color('gray'),
            Stat::make('Installments paid', $this->money($liabilities['equipment_paid_to_date']))
                ->description($label)
                ->color('primary'),
            Stat::make('Equipment balance left', $this->money($liabilities['equipment_balance_total']))
                ->description('Remaining capital item balance')
                ->color($liabilities['equipment_balance_total'] > 0 ? 'warning' : 'success'),
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
