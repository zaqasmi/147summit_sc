@php
    $fieldValue = function (string $key, mixed $default = null) use ($get, $record): mixed {
        $value = $get($key);

        if (($value !== null) && ($value !== '')) {
            return $value;
        }

        if ($record) {
            $recordValue = $record->getAttribute($key);

            if (($recordValue !== null) && ($recordValue !== '')) {
                return $recordValue;
            }
        }

        return $default;
    };

    $month = $fieldValue('month', today()->startOfMonth());
    $monthStart = \Illuminate\Support\Carbon::parse($month)->startOfMonth();
    $defaults = \App\Models\MonthlyClosing::defaultsForMonth($monthStart);
    $moneyValue = fn (string $key): float => round((float) $fieldValue($key, $defaults[$key] ?? 0), 2);

    $constructionDeduction = $moneyValue('construction_deduction_amount');
    $constructionReceived = $moneyValue('construction_received_amount');
    $closingOverride = [
        ...$defaults,
        'month' => $monthStart->toDateString(),
        'status' => $fieldValue('status', $defaults['status']),
        'rent_total' => $moneyValue('rent_total'),
        'rent_paid_amount' => $moneyValue('rent_paid_amount'),
        'rent_paid_from' => $fieldValue('rent_paid_from', $defaults['rent_paid_from']),
        'construction_deduction_amount' => $constructionDeduction,
        'construction_received_amount' => $constructionReceived,
        'construction_account_name' => $fieldValue('construction_account_name', $defaults['construction_account_name']),
        'construction_balance' => round(max(0, $constructionDeduction - $constructionReceived), 2),
        'liabilities_verified' => (bool) $fieldValue('liabilities_verified', $defaults['liabilities_verified']),
        'notes' => $fieldValue('notes', $defaults['notes']),
        'commission_paid_overrides' => $get('commission_paid_overrides') ?? [],
    ];

    $report = app(\App\Services\ReportService::class)->monthly($monthStart, $closingOverride);
    $commission = $report['staff_commission_totals'];
    $closing = $report['monthly_closing'];
    $tableNumbers = $report['table_numbers'] ?? [1, 2, 3, 4];
    $monthLabel = $report['month']->format('F Y');
    $periodLabel = \Illuminate\Support\Carbon::parse($report['period_start'])->format('d M Y').' to '.\Illuminate\Support\Carbon::parse($report['period_end'])->format('d M Y');
    $rentPaidFromLabel = \App\Models\MonthlyClosing::paidFromOptions()[$closing['rent_paid_from'] ?? 'bank'] ?? str_replace('_', ' ', ucfirst((string) ($closing['rent_paid_from'] ?? 'bank')));
    $statusLabel = \App\Models\MonthlyClosing::statusOptions()[$closingOverride['status']] ?? str_replace('_', ' ', ucfirst((string) $closingOverride['status']));
    $money = fn (mixed $amount): string => 'Rs '.number_format((float) ($amount ?? 0), 2);
    $compactMoney = function (mixed $amount): string {
        $amount = (float) ($amount ?? 0);

        return abs($amount - round($amount)) < 0.005
            ? number_format($amount, 0)
            : number_format($amount, 2);
    };
    $percent = fn (mixed $amount): string => number_format((float) ($amount ?? 0), 2).'%';

    $snapshotRows = array_filter([
        ['label' => 'Report month', 'basis' => 'Selected monthly closing', 'value' => $monthLabel, 'numeric' => false],
        ['label' => 'Period', 'basis' => 'Day-wise rows included', 'value' => $periodLabel, 'numeric' => false],
        ['label' => 'Closing status', 'basis' => 'Monthly closing record', 'value' => $statusLabel, 'numeric' => false],
        ['label' => 'Gross sale', 'basis' => 'Before customer dues', 'value' => $money($report['gross_sales_total'])],
        ['label' => 'Net customer dues', 'basis' => 'Dues added less recovered and discounts', 'value' => $money($report['dues_net_change'])],
        ['label' => 'Sale after dues', 'basis' => 'Gross sale after customer dues', 'value' => $money($report['sales_total'])],
        ['label' => 'Daily expenses', 'basis' => 'Non-rent expenses', 'value' => $money($report['daily_expense_total'])],
        ['label' => 'Rent deduction', 'basis' => 'Full monthly rent before distribution', 'value' => $money($report['rent_expense_total'])],
        ['label' => 'Total expenses', 'basis' => 'Daily expenses + rent deduction', 'value' => $money($report['expense_total'])],
        ['label' => 'Cash collected', 'basis' => 'Actual cash collected in month', 'value' => $money($report['cash_collected'])],
        ['label' => 'Collection after rent', 'basis' => 'Cash collected - rent deduction', 'value' => $money($report['collection_after_rent'])],
        ['label' => 'Distribution base', 'basis' => 'Net profit after rent adjustment', 'value' => $money($report['commission_distribution_base'])],
        ['label' => 'Commission rate', 'basis' => 'Effective monthly staff rate', 'value' => $percent($report['overall_commission_rate']), 'numeric' => false],
        ['label' => 'Commission earned', 'basis' => 'Distribution base x commission rate', 'value' => $money($commission['monthly_commission_to_be_paid'])],
        ['label' => 'Advance paid', 'basis' => 'Staff advances recorded for this month', 'value' => $money($commission['advance_paid'])],
        ['label' => 'Paid at monthly closing', 'basis' => 'Payments entered in this closing', 'value' => $money($commission['generated_paid'])],
        ['label' => 'Remaining staff dues', 'basis' => 'Unpaid balances including previous months', 'value' => $money(collect($report['staff_shares'])->sum(fn ($row) => max(0, $row['remaining_balance'])))],
        ['label' => 'Advance carried forward', 'basis' => 'Overpaid balances available next month', 'value' => $money(collect($report['staff_shares'])->sum(fn ($row) => max(0, -$row['remaining_balance'])))],
        ['label' => 'Paid commission', 'basis' => 'Advances, payouts, and generated paid amounts', 'value' => $money($commission['already_paid_this_month'])],
        ['label' => 'Remaining commission', 'basis' => 'Commission earned less paid commission', 'value' => $money($commission['monthly_remaining'])],
        ['label' => 'Overall staff remaining', 'basis' => 'Previous balance + current month less paid', 'value' => $money($commission['remaining_balance'])],
        filled($closing['notes'] ?? null) ? ['label' => 'Closing notes', 'basis' => 'Saved note', 'value' => $closing['notes'], 'numeric' => false] : null,
    ]);

    $closingRows = [
        ['item' => 'Total rent', 'basis' => 'Monthly closing rent amount', 'value' => $money($closing['rent_total'])],
        ['item' => 'Rent paid', 'basis' => $rentPaidFromLabel, 'value' => $money($closing['rent_paid_amount'])],
        ['item' => 'Construction deduction', 'basis' => 'Rent withheld for construction', 'value' => $money($closing['construction_deduction_amount'])],
        ['item' => 'Saved in other account', 'basis' => $closing['construction_account_name'] ?: 'Other account', 'value' => $money($closing['construction_received_amount'])],
        ['item' => 'Construction recovery balance', 'basis' => 'Deducted less saved in other account', 'value' => $money($closing['construction_balance'])],
        ['item' => 'Liabilities paid', 'basis' => 'Capital liability payments in period', 'value' => $money($report['capital_installments_paid'])],
        ['item' => 'Liabilities verified', 'basis' => 'Monthly closing check', 'value' => $closing['liabilities_verified'] ? 'Verified' : 'Pending', 'numeric' => false],
    ];

    $dailySummaryRows = [
        ['item' => 'Total sale in the month', 'basis' => 'Gross table sale before customer dues', 'value' => $money($report['gross_sales_total'])],
        ['item' => 'Sale after dues in the month', 'basis' => 'Gross sale - dues added + dues recovered', 'value' => $money($report['sales_total'])],
        ['item' => 'Total dues in the month', 'basis' => 'Customer dues added during this month', 'value' => $money($report['dues_added'])],
        ['item' => 'Total dues recovered in the month', 'basis' => 'Customer dues payments received during this month', 'value' => $money($report['dues_recovered'])],
        ['item' => 'Total dues remaining in the month', 'basis' => 'Dues added - recovered - discounted for this month', 'value' => $money($report['dues_net_change'])],
        ['item' => 'Overall dues remaining', 'basis' => 'Customer due balance at period end', 'value' => $money($report['dues']['balance_total'] ?? 0)],
        ['item' => 'Total expenses in the month', 'basis' => 'Daily expenses + rent deduction', 'value' => $money($report['expense_total'])],
        ['item' => 'Total advance paid in the month', 'basis' => 'Commission staff advances deducted from month', 'value' => $money($commission['advance_paid'])],
        ['item' => 'Total commission staff-wise', 'basis' => 'Total monthly commission for all commission staff', 'value' => $money($commission['monthly_commission_to_be_paid'])],
        ['item' => 'Total to be paid', 'basis' => 'Remaining amount payable to commission staff', 'value' => $money($commission['total_to_be_paid_this_month'])],
    ];

    $statRows = [
        ['item' => 'Sessions', 'value' => number_format((int) $report['sessions_count'])],
        ['item' => 'Frames', 'value' => number_format((int) $report['frames_count'])],
        ['item' => 'Manual closing days', 'value' => number_format((int) $report['manual_days'])],
        ['item' => 'System closing days', 'value' => number_format((int) $report['system_days'])],
        ['item' => 'Customer dues added', 'value' => $money($report['dues_added'])],
        ['item' => 'Customer dues recovered', 'value' => $money($report['dues_recovered'])],
        ['item' => 'Customer dues discounted', 'value' => $money($report['dues_discounted'])],
        ['item' => 'Deposited to bank', 'value' => $money($report['bank_deposit_amount'])],
    ];
@endphp

<div class="summit-monthly-closing-snapshot">
    <div class="summit-monthly-closing-snapshot-header">
        <div>
            <div class="summit-monthly-closing-snapshot-title">Printable monthly closing report</div>
            <div class="summit-monthly-closing-snapshot-subtitle">{{ $monthLabel }} · {{ $periodLabel }}</div>
        </div>

        <button
            type="button"
            onclick="document.body.classList.add('summit-print-monthly-closing-snapshot-only'); window.addEventListener('afterprint', () => document.body.classList.remove('summit-print-monthly-closing-snapshot-only'), { once: true }); window.print(); setTimeout(() => document.body.classList.remove('summit-print-monthly-closing-snapshot-only'), 1200)"
            class="summit-print-button summit-print-button-secondary"
        >
            Print closing report
        </button>
    </div>

    @include('filament.components.cash-bank-summary', ['asOf' => $report['period_end']])

    <div class="summit-panel summit-print-priority-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
            <div class="font-semibold">Daily table sales, customer dues, and actual collection</div>
            <div class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $monthLabel }}</div>
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-daily-closing-print-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Day</th>
                        @foreach ($tableNumbers as $tableNumber)
                            <th class="px-4 py-3 summit-money">T{{ $tableNumber }}</th>
                        @endforeach
                        <th class="px-4 py-3 summit-money">Sale</th>
                        <th class="px-4 py-3 summit-money">D+</th>
                        <th class="px-4 py-3 summit-money">D rec</th>
                        <th class="px-4 py-3 summit-money">D bal</th>
                        <th class="px-4 py-3 summit-money">Net</th>
                        <th class="px-4 py-3 summit-money">Exp</th>
                        <th class="px-4 py-3 summit-money">Cash</th>
                        <th class="px-4 py-3 summit-money">Stf</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['daily_rows'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d') }}</td>
                            @foreach ($tableNumbers as $tableNumber)
                                <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['table_sales_by_number'][$tableNumber] ?? 0) }}</td>
                            @endforeach
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['gross_sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['dues_added']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['dues_recovered']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['dues_net_change']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['daily_expense_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['cash_collected']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($row['staff_paid_total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3" colspan="{{ count($tableNumbers) + 9 }}">No daily closing activity found for this month.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="px-4 py-3 font-semibold">Total</td>
                        @foreach ($tableNumbers as $tableNumber)
                            <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['table_sales_by_number'][$tableNumber] ?? 0) }}</td>
                        @endforeach
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['gross_sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['dues_added']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['dues_recovered']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['dues_net_change']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['daily_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['cash_collected']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $compactMoney($report['staff_paid_total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Monthly totals below daily table
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-compact-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Particular</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailySummaryRows as $row)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $row['item'] }}</td>
                            <td class="px-4 py-3">{{ $row['basis'] }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Staff-wise commission and total to be paid
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Staff</th>
                        <th class="px-4 py-3">Distribution</th>
                        <th class="px-4 py-3">Rate of profit</th>
                        <th class="px-4 py-3 summit-money">Previous balance</th>
                        <th class="px-4 py-3 summit-money">Monthly commission</th>
                        <th class="px-4 py-3 summit-money">Advance</th>
                        <th class="px-4 py-3 summit-money">Paid</th>
                        <th class="px-4 py-3">Closing payment from</th>
                        <th class="px-4 py-3 summit-money">Already paid this month</th>
                        <th class="px-4 py-3 summit-money">Total to be paid this month</th>
                        <th class="px-4 py-3 summit-money">Overall remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['staff_shares'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['staff']->name }}</td>
                            <td class="px-4 py-3">{{ $percent($row['distribution_rate']) }}</td>
                            <td class="px-4 py-3">{{ $percent($row['commission_rate']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['previous_balance']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['monthly_commission_to_be_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['advance_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['payout_paid'] + $row['paid_amount']) }}</td>
                            <td class="px-4 py-3">{{ \App\Models\StaffTransaction::paidFromOptions()[$get('commission_payment_sources')[$row['staff']->id] ?? $row['paid_from'] ?? ''] ?? 'Not recorded' }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['already_paid_this_month']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['total_to_be_paid_this_month']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $money($row['remaining_balance']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3" colspan="11">No active commission staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="px-4 py-3 font-semibold" colspan="3">Overall total</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['previous_balance']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['monthly_commission_to_be_paid']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['advance_paid']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money((float) $commission['payout_paid'] + (float) $commission['generated_paid']) }}</td>
                        <td class="px-4 py-3">—</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['already_paid_this_month']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['total_to_be_paid_this_month']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['remaining_balance']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Report snapshot
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-snapshot-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Particular</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($snapshotRows as $row)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">{{ $row['basis'] }}</td>
                            <td class="px-4 py-3 font-semibold {{ ($row['numeric'] ?? true) ? 'summit-money' : '' }}">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-monthly-closing-snapshot-list">
        {{ $getChildSchema() }}
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Month totals after expenses, rent, and commission
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3 summit-money">Gross sale</th>
                        <th class="px-4 py-3 summit-money">Net customer dues</th>
                        <th class="px-4 py-3 summit-money">Sale after dues</th>
                        <th class="px-4 py-3 summit-money">Daily expense</th>
                        <th class="px-4 py-3 summit-money">Rent deduction</th>
                        <th class="px-4 py-3 summit-money">Total expense</th>
                        <th class="px-4 py-3 summit-money">Cash collected</th>
                        <th class="px-4 py-3 summit-money">Collection after rent</th>
                        <th class="px-4 py-3 summit-money">Commission</th>
                        <th class="px-4 py-3 summit-money">Paid commission</th>
                        <th class="px-4 py-3 summit-money">Remaining commission</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3">{{ $monthLabel }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['gross_sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['dues_net_change']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['daily_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['rent_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['cash_collected']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['collection_after_rent']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['monthly_commission_to_be_paid']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['already_paid_this_month']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['monthly_remaining']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Rent, liabilities, and recovery
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-compact-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($closingRows as $row)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $row['item'] }}</td>
                            <td class="px-4 py-3">{{ $row['basis'] }}</td>
                            <td class="px-4 py-3 font-semibold {{ ($row['numeric'] ?? true) ? 'summit-money' : '' }}">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Overall commission summary
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-compact-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Particular</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3">Distribution base</td>
                        <td class="px-4 py-3">Net profit after rent deduction</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($report['commission_distribution_base']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Commission rate</td>
                        <td class="px-4 py-3">Effective rate for active commission staff</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $percent($report['overall_commission_rate']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Commission earned</td>
                        <td class="px-4 py-3">{{ $money($report['commission_distribution_base']) }} x {{ $percent($report['overall_commission_rate']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['monthly_commission_to_be_paid']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Previous staff balance</td>
                        <td class="px-4 py-3">Remaining balance brought from previous months</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['previous_balance']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Advance paid in month</td>
                        <td class="px-4 py-3">Cash or bank staff advances deducted from commission</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['advance_paid']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Final paid in month</td>
                        <td class="px-4 py-3">Commission payouts and generated paid amounts</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money((float) $commission['payout_paid'] + (float) $commission['generated_paid']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Total paid in month</td>
                        <td class="px-4 py-3">Advances + final payments</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['already_paid_this_month']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Remaining commission</td>
                        <td class="px-4 py-3">Commission earned - total paid in month</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['monthly_remaining']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Overall remaining staff balance</td>
                        <td class="px-4 py-3">Previous balance + commission earned - total paid</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $money($commission['remaining_balance']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Other closing stats
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table summit-compact-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Stat</th>
                        <th class="px-4 py-3 summit-money">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statRows as $row)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $row['item'] }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $row['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
