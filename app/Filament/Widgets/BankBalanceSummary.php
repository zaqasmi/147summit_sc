<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class BankBalanceSummary extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Bank account';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $selectedDate = $this->selectedDate();
        $summary = app(ReportService::class)->bankSummary($selectedDate);
        $label = 'Till '.$selectedDate->format('d M Y');
        $otherOutflow = max(
            0,
            (float) $summary['supplier_payments']
                + (float) $summary['withdrawals']
                + (float) $summary['adjustments_out'],
        );

        return [
            Stat::make('Cash in bank', $this->money($summary['cash_in_bank']))
                ->description($label)
                ->color($summary['cash_in_bank'] >= 0 ? 'success' : 'danger'),
            Stat::make('Cash pending bank', $this->money($summary['collection_cash_pending_deposit']))
                ->description('After cash payments and construction recovery')
                ->color($summary['collection_cash_pending_deposit'] > 0 ? 'warning' : 'success'),
            Stat::make('Cash staff paid', $this->money($summary['cash_staff_payments_pending_deduction']))
                ->description('Deducted from pending cash')
                ->color($summary['cash_staff_payments_pending_deduction'] > 0 ? 'warning' : 'gray'),
            Stat::make('Cash rent paid', $this->money($summary['cash_rent_payments_pending_deduction']))
                ->description('Deducted from pending cash')
                ->color($summary['cash_rent_payments_pending_deduction'] > 0 ? 'warning' : 'gray'),
            Stat::make('Cash expenses', $this->money($summary['cash_expenses_pending_deduction']))
                ->description('Standalone non-rent expenses paid from cash')
                ->color($summary['cash_expenses_pending_deduction'] > 0 ? 'warning' : 'gray'),
            Stat::make('Cash installments', $this->money($summary['cash_installments_pending_deduction']))
                ->description('Liability payments paid from cash')
                ->color($summary['cash_installments_pending_deduction'] > 0 ? 'warning' : 'gray'),
            Stat::make('Construction saved', $this->money($summary['construction_other_account_pending_deduction']))
                ->description('Received in other account')
                ->color($summary['construction_other_account_pending_deduction'] > 0 ? 'info' : 'gray'),
            Stat::make('Collection deposits', $this->money($summary['daily_deposits']))
                ->description('Deposited on actual bank date')
                ->color('success'),
            Stat::make('Other received', $this->money($summary['other_payments_received']))
                ->description('Other payments deposited')
                ->color('success'),
            Stat::make('Bank loans received', $this->money($summary['loan_received']))
                ->description($label)
                ->color('info'),
            Stat::make('Staff payments', $this->money($summary['staff_payments']))
                ->description('Advances / payouts from bank')
                ->color($summary['staff_payments'] > 0 ? 'warning' : 'gray'),
            Stat::make('Expenses paid', $this->money($summary['expenses_paid']))
                ->description('Paid from bank')
                ->color($summary['expenses_paid'] > 0 ? 'danger' : 'gray'),
            Stat::make('Rent paid', $this->money($summary['rent_paid']))
                ->description('Closed monthly rent paid from bank')
                ->color($summary['rent_paid'] > 0 ? 'danger' : 'gray'),
            Stat::make('Loan returns paid', $this->money($summary['loan_installments_paid']))
                ->description('Paid from bank')
                ->color($summary['loan_installments_paid'] > 0 ? 'warning' : 'gray'),
            Stat::make('Other installments', $this->money($summary['non_loan_installments_paid']))
                ->description('Supplier / capital paid from bank')
                ->color($summary['non_loan_installments_paid'] > 0 ? 'warning' : 'gray'),
            Stat::make('Other bank outflow', $this->money($otherOutflow))
                ->description('Suppliers, withdrawals, adjustments')
                ->color($otherOutflow > 0 ? 'danger' : 'gray'),
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
