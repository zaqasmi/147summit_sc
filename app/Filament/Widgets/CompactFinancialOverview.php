<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class CompactFinancialOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.compact-financial-overview';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $selectedDate = $this->selectedDate();
        $reports = app(ReportService::class);
        $business = $reports->businessSummary($selectedDate, includeScheduledRent: true);
        $staffShare = $reports->staffShareSummary($selectedDate, includeScheduledRent: true);
        $bank = $reports->bankSummary($selectedDate);
        $capital = $reports->capitalSummary($selectedDate);
        $liabilities = $reports->liabilitySummary($selectedDate);
        $staffBalance = (float) $staffShare['balance_due_total'];
        $staffBalanceDescription = $staffBalance >= 0
            ? 'Remaining commission to pay.'
            : 'Advance overpaid; carry forward to next closing.';
        $label = 'Till '.$selectedDate->format('d M Y');

        return [
            'asOfLabel' => $label,
            'heroStats' => [
                $this->stat('Total collection', $business['total_collection'], 'Actual cash collected after unpaid customer dues.', 'heroicon-o-banknotes', 'success'),
                $this->stat('Cash in bank', $bank['cash_in_bank'], 'Only money actually deposited in bank minus bank outflows.', 'heroicon-o-building-library', ((float) $bank['cash_in_bank']) >= 0 ? 'success' : 'danger'),
                $this->stat('Cash pending bank', $bank['collection_cash_pending_deposit'], 'Collected cash not yet deposited after cash payments and construction recovery.', 'heroicon-o-inbox-stack', ((float) $bank['collection_cash_pending_deposit']) > 0 ? 'warning' : 'success'),
                $this->stat('Salesmen commission', $business['staff_commission'], 'Commission earned from cash profit.', 'heroicon-o-receipt-percent', 'warning'),
                $this->stat('My profit', $business['my_profit'], 'Owner profit after expenses and staff commission.', 'heroicon-o-arrow-trending-up', ((float) $business['my_profit']) >= 0 ? 'success' : 'danger'),
                $this->stat('Dues balance', $business['dues_balance_total'], 'Customer amount still pending.', 'heroicon-o-user-minus', ((float) $business['dues_balance_total']) > 0 ? 'danger' : 'success'),
            ],
            'sections' => [
                [
                    'title' => 'Sales and collection',
                    'description' => 'Cash-basis sales after customer dues, with expenses kept visible.',
                    'accent' => 'emerald',
                    'icon' => 'heroicon-o-chart-bar',
                    'stats' => [
                        $this->stat('Gross sale', $business['gross_sales_total'], 'Table sale before dues deduction.', 'heroicon-o-currency-rupee', 'success'),
                        $this->stat('Customer dues', $business['dues_added'], 'New unpaid customer amount.', 'heroicon-o-user-minus', 'danger'),
                        $this->stat('Expenses', $business['expense_total'], 'Operating cost recorded so far.', 'heroicon-o-receipt-refund', 'warning'),
                        $this->stat('Dues recovered', $business['dues_recovered'], 'Customer due payments collected.', 'heroicon-o-arrow-down-tray', 'success'),
                    ],
                ],
                [
                    'title' => 'Profit and staff',
                    'description' => 'Commission and owner profit based on actual cash collected.',
                    'accent' => 'sky',
                    'icon' => 'heroicon-o-users',
                    'stats' => [
                        $this->stat('Staff commission', $business['staff_commission'], 'Commission earned from cash profit.', 'heroicon-o-receipt-percent', 'warning'),
                        $this->stat('Staff paid', $staffShare['amount_paid_total'], 'Advances and payouts already paid.', 'heroicon-o-wallet', 'success'),
                        $this->stat('Staff balance', $staffBalance, $staffBalanceDescription, 'heroicon-o-scale', $staffBalance > 0 ? 'warning' : 'info'),
                        $this->stat('Active staff', $staffShare['staff_count'], 'Staff included in commission split.', 'heroicon-o-user-group', 'info', asMoney: false),
                    ],
                ],
                [
                    'title' => 'Bank account',
                    'description' => 'Real bank movement plus cash still outside bank after cash payments and construction recovery.',
                    'accent' => 'violet',
                    'icon' => 'heroicon-o-building-library',
                    'stats' => [
                        $this->stat('Collection deposits', $bank['daily_deposits'], 'Collected cash deposited on actual bank deposit dates.', 'heroicon-o-arrow-down-circle', 'success'),
                        $this->stat('Pending deposit', $bank['collection_cash_pending_deposit'], 'Actual collected cash still outside bank after cash payments and construction recovery.', 'heroicon-o-inbox-stack', ((float) $bank['collection_cash_pending_deposit']) > 0 ? 'warning' : 'success'),
                        $this->stat('Cash staff paid', $bank['cash_staff_payments_pending_deduction'], 'Cash payments to staff deducted from pending deposit.', 'heroicon-o-wallet', ((float) $bank['cash_staff_payments_pending_deduction']) > 0 ? 'warning' : 'gray'),
                        $this->stat('Cash rent paid', $bank['cash_rent_payments_pending_deduction'], 'Monthly rent paid from cash deducted from pending deposit.', 'heroicon-o-home-modern', ((float) $bank['cash_rent_payments_pending_deduction']) > 0 ? 'warning' : 'gray'),
                        $this->stat('Cash expenses', $bank['cash_expenses_pending_deduction'], 'Standalone non-rent expenses paid from cash.', 'heroicon-o-receipt-refund', ((float) $bank['cash_expenses_pending_deduction']) > 0 ? 'warning' : 'gray'),
                        $this->stat('Cash installments', $bank['cash_installments_pending_deduction'], 'Liability installments paid from cash deducted from pending deposit.', 'heroicon-o-scale', ((float) $bank['cash_installments_pending_deduction']) > 0 ? 'warning' : 'gray'),
                        $this->stat('Construction saved', $bank['construction_other_account_pending_deduction'], 'Construction recovery received in other account.', 'heroicon-o-archive-box-arrow-down', ((float) $bank['construction_other_account_pending_deduction']) > 0 ? 'info' : 'gray'),
                        $this->stat('Other received', $bank['other_payments_received'], 'Other payments received for bank deposit.', 'heroicon-o-inbox-arrow-down', 'success'),
                        $this->stat('Loans received', $bank['loan_received'], 'Loan money received in the bank.', 'heroicon-o-banknotes', 'info'),
                        $this->stat('Staff payments', $bank['staff_payments'], 'Staff advances and payouts from bank.', 'heroicon-o-wallet', 'warning'),
                        $this->stat('Expenses paid', $bank['expenses_paid'], 'Expenses paid directly from bank.', 'heroicon-o-receipt-refund', 'danger'),
                        $this->stat('Loan returns', $bank['loan_installments_paid'], 'Loan repayments made from bank.', 'heroicon-o-arrow-up-circle', 'warning'),
                        $this->stat('Other installments', $bank['non_loan_installments_paid'], 'Supplier and capital installments.', 'heroicon-o-scale', 'warning'),
                        $this->stat('Other outflow', $this->otherBankOutflow($bank), 'Supplier payments, withdrawals, adjustments.', 'heroicon-o-arrow-right-circle', 'danger'),
                    ],
                ],
                [
                    'title' => 'Capital and liabilities',
                    'description' => 'Capital recovery, loan balances, and supplier/equipment installments stay separate from earnings.',
                    'accent' => 'amber',
                    'icon' => 'heroicon-o-briefcase',
                    'stats' => [
                        $this->stat('Capital invested', $capital['capital_invested'], 'Net owner capital amount to recover.', 'heroicon-o-currency-rupee', 'info'),
                        $this->stat('Capital added', $capital['capital_added'], 'Manual capital and owner-paid liabilities.', 'heroicon-o-plus-circle', 'info'),
                        $this->stat('Recovery income', $capital['capital_reduced'], 'Separate recovery entries recorded.', 'heroicon-o-arrow-trending-down', ((float) $capital['capital_reduced']) > 0 ? 'success' : 'gray'),
                        $this->stat('Profit covered', $capital['capital_recovered'], 'Capital covered by owner profit.', 'heroicon-o-check-badge', ((float) $capital['capital_recovered']) > 0 ? 'success' : 'gray'),
                        $this->stat('Capital remaining', $capital['capital_remaining'], 'Capital still not recovered.', 'heroicon-o-arrow-path', ((float) $capital['capital_remaining']) > 0 ? 'warning' : 'success'),
                        $this->stat('Liability paid', $liabilities['paid_to_date'], 'Total installments paid so far.', 'heroicon-o-check-circle', 'success'),
                        $this->stat('Loan left', $liabilities['loan_balance_total'], 'Remaining loan balance.', 'heroicon-o-exclamation-triangle', ((float) $liabilities['loan_balance_total']) > 0 ? 'danger' : 'success'),
                        $this->stat('Equipment left', $liabilities['equipment_balance_total'], 'Supplier/equipment balance unpaid.', 'heroicon-o-wrench-screwdriver', ((float) $liabilities['equipment_balance_total']) > 0 ? 'warning' : 'success'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{label: string, value: string, description: string, icon: string, color: string}
     */
    private function stat(
        string $label,
        float|int|string|null $amount,
        string $description,
        string $icon,
        string $color,
        bool $asMoney = true,
    ): array {
        return [
            'label' => $label,
            'value' => $asMoney ? $this->money($amount) : number_format((float) $amount),
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
        ];
    }

    /**
     * @param  array<string, float|int|string>  $bank
     */
    private function otherBankOutflow(array $bank): float
    {
        return round(
            max(
                0,
                (float) $bank['supplier_payments']
                    + (float) $bank['withdrawals']
                    + (float) $bank['adjustments_out'],
            ),
            2,
        );
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
