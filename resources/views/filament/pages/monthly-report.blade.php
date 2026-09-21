<x-filament-panels::page>
    @php($report = $this->report())
    @php($commission = $report['staff_commission_totals'])
    @php($closing = $report['monthly_closing'])
    @php($monthClosed = $this->isMonthClosed())
    @php($canManageMonthlyClosing = $this->canManageMonthlyClosing())
    @php($canViewOwnerProfit = $this->canViewOwnerProfit())
    @php($tableNumbers = $report['table_numbers'] ?? [1, 2, 3, 4])
    @php($rentSplitDifference = round((float) $closing['rent_total'] - (float) $closing['rent_paid_amount'] - (float) $closing['construction_deduction_amount'], 2))
    @php($rentPaidFromLabel = ['bank' => 'Bank', 'cash' => 'Cash from collection'][$closing['rent_paid_from'] ?? 'bank'] ?? ucfirst(str_replace('_', ' ', (string) ($closing['rent_paid_from'] ?? 'bank'))))
    @php($closingSourceLabel = ucfirst(str_replace('_', ' ', (string) $closing['source'])))
    @php($monthlyStats = array_filter([
        ['label' => 'Overall commission rate', 'value' => $this->percent($report['overall_commission_rate']), 'tone' => 'amber'],
        ['label' => 'Cash collected', 'value' => $this->money($report['cash_collected']), 'tone' => 'green'],
        ['label' => 'Dues discounted', 'value' => $this->money($report['dues_discounted']), 'tone' => 'amber'],
        ['label' => 'Monthly rent', 'value' => $this->money($report['rent_expense_total']), 'tone' => 'rose'],
        ['label' => 'Distribution base after rent', 'value' => $this->money($report['commission_distribution_base']), 'tone' => 'teal'],
        ['label' => 'Total commission in month', 'value' => $this->money($commission['monthly_commission_to_be_paid']), 'tone' => 'teal'],
        ['label' => 'Paid commission', 'value' => $this->money($commission['already_paid_this_month']), 'tone' => 'green'],
        ['label' => 'Remaining commission', 'value' => $this->money($commission['monthly_remaining']), 'tone' => ((float) $commission['monthly_remaining']) > 0 ? 'amber' : 'green'],
        $canViewOwnerProfit ? ['label' => 'Owner profit', 'value' => $this->money($report['owner_profit_after_staff_share']), 'tone' => ((float) $report['owner_profit_after_staff_share']) >= 0 ? 'green' : 'rose'] : null,
        ['label' => 'Previous advance balance', 'value' => $this->money($report['staff_advance_carry_in']), 'tone' => 'amber'],
        ['label' => 'Net payable / carry forward', 'value' => $this->money($report['staff_distribution_to_be_paid']), 'tone' => 'green'],
    ]))
    @php($snapshotRows = array_filter([
        ['label' => 'Report month', 'basis' => 'Selected period', 'value' => $report['month']->format('F Y'), 'numeric' => false],
        ['label' => 'Closing status', 'basis' => 'Monthly closing record', 'value' => $monthClosed ? 'Closed' : 'Draft', 'numeric' => false],
        ['label' => 'Gross sale', 'basis' => 'Before customer dues', 'value' => $this->money($report['gross_sales_total'])],
        ['label' => 'Net customer dues', 'basis' => 'Dues added less recovered and discounts', 'value' => $this->money($report['dues_net_change'])],
        ['label' => 'Sale after dues', 'basis' => 'Gross sale after customer dues', 'value' => $this->money($report['sales_total'])],
        ['label' => 'Daily expenses', 'basis' => 'Non-rent expenses', 'value' => $this->money($report['daily_expense_total'])],
        ['label' => 'Monthly rent', 'basis' => 'Full rent deducted before distribution', 'value' => $this->money($report['rent_expense_total'])],
        ['label' => 'Actual rent paid', 'basis' => $rentPaidFromLabel, 'value' => $this->money($closing['rent_paid_amount'])],
        ['label' => 'Construction deduction', 'basis' => 'Rent amount withheld for construction', 'value' => $this->money($closing['construction_deduction_amount'])],
        ['label' => 'Saved in other account', 'basis' => $closing['construction_account_name'] ?: 'Other account', 'value' => $this->money($closing['construction_received_amount'])],
        ['label' => 'Construction recovery balance', 'basis' => 'Deducted less saved in other account', 'value' => $this->money($closing['construction_balance'])],
        ['label' => 'Cash collected', 'basis' => 'Actual cash collected in month', 'value' => $this->money($report['cash_collected'])],
        ['label' => 'Distribution base after rent', 'basis' => 'Net profit after rent', 'value' => $this->money($report['commission_distribution_base'])],
        ['label' => 'Commission rate', 'basis' => 'Effective monthly rate', 'value' => $this->percent($report['overall_commission_rate']), 'numeric' => false],
        ['label' => 'Total commission in month', 'basis' => 'Distribution base x rate', 'value' => $this->money($commission['monthly_commission_to_be_paid'])],
        ['label' => 'Paid commission', 'basis' => 'Advances, payouts, and generated paid amounts', 'value' => $this->money($commission['already_paid_this_month'])],
        ['label' => 'Remaining commission', 'basis' => 'Commission less paid commission', 'value' => $this->money($commission['monthly_remaining'])],
        $canViewOwnerProfit ? ['label' => 'Owner profit', 'basis' => 'Net profit less commission', 'value' => $this->money($report['owner_profit_after_staff_share'])] : null,
        ['label' => 'Previous advance balance', 'basis' => 'Negative amount carried into month', 'value' => $this->money($report['staff_advance_carry_in'])],
        ['label' => 'Net payable / carry forward', 'basis' => 'Commission plus carry-in less paid amounts', 'value' => $this->money($report['staff_distribution_to_be_paid'])],
        filled($closing['notes'] ?? null) ? ['label' => 'Closing notes', 'basis' => 'Saved note', 'value' => $closing['notes'], 'numeric' => false] : null,
    ]))

    <div class="summit-report-toolbar">
        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
            Report month
            <input
                type="month"
                wire:model.live="month"
                class="summit-date-input"
            />
        </label>

        <button
            type="button"
            onclick="document.body.classList.remove('summit-print-snapshot-only'); window.print()"
            class="summit-print-button"
        >
            Print report
        </button>
    </div>

    <div class="summit-stat-grid">
        @foreach ($monthlyStats as $stat)
            <div class="summit-stat-card" data-tone="{{ $stat['tone'] }}">
                <div class="summit-stat-label">{{ $stat['label'] }}</div>
                <div class="summit-stat-value">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="summit-panel summit-closing-panel bg-white dark:bg-gray-900">
        <div class="summit-closing-header">
            <div>
                <div class="font-semibold text-gray-950 dark:text-white">Monthly closing</div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Rent split, payment source, construction recovery, and final close controls.
                </div>
            </div>

            <div class="summit-closing-actions">
                <div class="summit-status-badge" data-status="{{ $monthClosed ? 'closed' : 'draft' }}">
                    {{ $monthClosed ? 'Closed' : 'Draft' }}
                </div>

                @if ($canManageMonthlyClosing)
                    <button type="button" wire:click="saveMonthlyClosingDraft" @disabled($monthClosed) class="summit-print-button summit-print-button-secondary">
                        Save draft
                    </button>
                    <button type="button" wire:click="closeMonth" @disabled($monthClosed) class="summit-print-button">
                        Close month
                    </button>
                @endif
            </div>
        </div>

        <div class="summit-closing-body">
            <div class="overflow-x-auto">
                <table class="summit-table summit-closing-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Closing item</th>
                            <th class="px-4 py-3">Value</th>
                            <th class="px-4 py-3">Ledger posting</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Total rent</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="number" min="0" step="0.01" wire:model.live="rentTotal" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
                            </td>
                            <td class="px-4 py-3">Distribution expense</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Rent paid</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="number" min="0" step="0.01" wire:model.live="rentPaidAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
                            </td>
                            <td class="px-4 py-3">Bank outflow or pending cash deduction</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Paid from</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <select wire:model.live="rentPaidFrom" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input">
                                    <option value="bank">Bank</option>
                                    <option value="cash">Cash from collection</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">{{ $rentPaidFromLabel }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Construction deduction</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="number" min="0" step="0.01" wire:model.live="constructionDeductionAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
                            </td>
                            <td class="px-4 py-3">Unpaid rent allocation</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Saved in other account</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="number" min="0" step="0.01" wire:model.live="constructionReceivedAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
                            </td>
                            <td class="px-4 py-3">Pending cash deduction</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Other account name</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="text" wire:model.live="constructionAccountName" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
                            </td>
                            <td class="px-4 py-3">{{ $closing['construction_account_name'] ?: 'Other account' }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Liabilities verified</td>
                            <td class="px-4 py-3">
                                <label class="summit-checkbox-line">
                                    <input type="checkbox" wire:model.live="liabilitiesVerified" @disabled($monthClosed || ! $canManageMonthlyClosing) class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                                    <span>{{ $closing['liabilities_verified'] ? 'Verified' : 'Pending' }}</span>
                                </label>
                            </td>
                            <td class="px-4 py-3">{{ $this->money($report['capital_installments_paid']) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Closing notes</td>
                            <td class="px-4 py-3 summit-form-cell">
                                <input type="text" wire:model.live="closingNotes" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input summit-notes-input" />
                            </td>
                            <td class="px-4 py-3">{{ filled($closing['notes'] ?? null) ? $closing['notes'] : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto">
                <table class="summit-table summit-compact-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Closing check</th>
                            <th class="px-4 py-3 summit-money">Amount</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Rent split difference</td>
                            <td class="px-4 py-3 summit-money font-semibold {{ abs($rentSplitDifference) > 0.01 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}">{{ $this->money($rentSplitDifference) }}</td>
                            <td class="px-4 py-3">{{ abs($rentSplitDifference) > 0.01 ? 'Needs review' : 'Balanced' }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Construction recovery balance</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($closing['construction_balance']) }}</td>
                            <td class="px-4 py-3">{{ $closing['construction_balance'] > 0 ? 'Open' : 'Clear' }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Total construction deducted</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['construction_deductions']['deducted_total']) }}</td>
                            <td class="px-4 py-3">{{ $closingSourceLabel }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-semibold">Total saved other account</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['construction_deductions']['received_total']) }}</td>
                            <td class="px-4 py-3">{{ $closing['construction_account_name'] ?: 'Other account' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="summit-panel summit-report-snapshot bg-white dark:bg-gray-900">
        <div class="summit-snapshot-header">
            <div>
                <div class="font-semibold text-gray-950 dark:text-white">Report snapshot</div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $report['month']->format('F Y') }} closing figures
                </div>
            </div>

            <button
                type="button"
                onclick="document.body.classList.add('summit-print-snapshot-only'); window.addEventListener('afterprint', () => document.body.classList.remove('summit-print-snapshot-only'), { once: true }); window.print(); setTimeout(() => document.body.classList.remove('summit-print-snapshot-only'), 1200)"
                class="summit-print-button summit-print-button-secondary"
            >
                Print snapshot
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="summit-table summit-snapshot-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Particular</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Snapshot</th>
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

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Daily table sales, customer dues, and actual collection
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        @foreach ($tableNumbers as $tableNumber)
                            <th class="px-4 py-3 summit-money">Table {{ $tableNumber }}</th>
                        @endforeach
                        <th class="px-4 py-3 summit-money">Gross sale</th>
                        <th class="px-4 py-3 summit-money">Net customer dues</th>
                        <th class="px-4 py-3 summit-money">Sale after dues</th>
                        <th class="px-4 py-3 summit-money">Daily expense</th>
                        <th class="px-4 py-3 summit-money">Actual collected</th>
                        <th class="px-4 py-3 summit-money">Staff paid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['daily_rows'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M') }}</td>
                            @foreach ($tableNumbers as $tableNumber)
                                <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['table_sales_by_number'][$tableNumber] ?? 0) }}</td>
                            @endforeach
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['gross_sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['dues_net_change']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['daily_expense_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['cash_collected']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['staff_paid_total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3" colspan="{{ count($tableNumbers) + 7 }}">No daily closing activity found for this month.</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td class="px-4 py-3 font-semibold">Month total</td>
                        @foreach ($tableNumbers as $tableNumber)
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['table_sales_by_number'][$tableNumber] ?? 0) }}</td>
                        @endforeach
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['gross_sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['dues_net_change']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['daily_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['cash_collected']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Month totals after dues, rent, and staff payments
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
                        <th class="px-4 py-3 summit-money">Monthly rent</th>
                        <th class="px-4 py-3 summit-money">Total expense</th>
                        <th class="px-4 py-3 summit-money">Total collection</th>
                        <th class="px-4 py-3 summit-money">Collection after rent</th>
                        <th class="px-4 py-3 summit-money">Total commission in month</th>
                        <th class="px-4 py-3 summit-money">Paid commission</th>
                        <th class="px-4 py-3 summit-money">Remaining commission</th>
                        @if ($canViewOwnerProfit)
                            <th class="px-4 py-3 summit-money">Owner profit</th>
                        @endif
                        <th class="px-4 py-3 summit-money">Previous advance balance</th>
                        <th class="px-4 py-3 summit-money">Net payable / carry forward</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3">{{ $report['month']->format('F Y') }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['gross_sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['dues_net_change']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['daily_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['rent_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['cash_collected']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['collection_after_rent']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['monthly_commission_to_be_paid']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['already_paid_this_month']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['monthly_remaining']) }}</td>
                        @if ($canViewOwnerProfit)
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['owner_profit_after_staff_share']) }}</td>
                        @endif
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_advance_carry_in']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold"><span class="summit-amount-badge summit-amount-badge-green">{{ $this->money($report['staff_distribution_to_be_paid']) }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Overall bifurcation
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Particular</th>
                        <th class="px-4 py-3">Basis</th>
                        <th class="px-4 py-3 summit-money">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-4 py-3">Daily expenses</td>
                        <td class="px-4 py-3">Daily closing and non-rent expense entries</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['daily_expense_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Monthly rent</td>
                        <td class="px-4 py-3">Rent category counted once for this month</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['rent_expense_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Distribution base after rent</td>
                        <td class="px-4 py-3">{{ $this->money($report['cash_collected']) }} - {{ $this->money($report['rent_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['commission_distribution_base']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Total commission in month</td>
                        <td class="px-4 py-3">{{ $this->money($report['commission_distribution_base']) }} x {{ $this->percent($report['overall_commission_rate']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['monthly_commission_to_be_paid']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Paid commission</td>
                        <td class="px-4 py-3">Advances, payouts, and generated commission paid in this month</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['already_paid_this_month']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Remaining commission</td>
                        <td class="px-4 py-3">Total commission in month - paid commission</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['monthly_remaining']) }}</td>
                    </tr>
                    @if ($canViewOwnerProfit)
                        <tr>
                            <td class="px-4 py-3">Owner profit</td>
                            <td class="px-4 py-3">Net profit - total commission in month</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['owner_profit_after_staff_share']) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="px-4 py-3">Previous advance balance</td>
                        <td class="px-4 py-3">Negative amount carried from previous months</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_advance_carry_in']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Net payable / carry forward</td>
                        <td class="px-4 py-3">Monthly commission + previous advance balance - advances and payouts</td>
                        <td class="px-4 py-3 summit-money font-semibold"><span class="summit-amount-badge summit-amount-badge-green">{{ $this->money($report['staff_distribution_to_be_paid']) }}</span></td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Advance carried forward</td>
                        <td class="px-4 py-3">Only negative balances carry into the next month</td>
                        <td class="px-4 py-3 summit-money font-semibold"><span class="summit-amount-badge summit-amount-badge-green">{{ $this->money($report['staff_advance_carry_forward']) }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">
            Individual bifurcation
        </div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Staff</th>
                        <th class="px-4 py-3">Distribution</th>
                        <th class="px-4 py-3">Rate of profit</th>
                        <th class="px-4 py-3 summit-money">Previous balance</th>
                        <th class="px-4 py-3 summit-money">Monthly commission to be paid</th>
                        <th class="px-4 py-3 summit-money">Advance</th>
                        <th class="px-4 py-3 summit-money">Paid</th>
                        <th class="px-4 py-3 summit-money">Already paid this month</th>
                        <th class="px-4 py-3 summit-money">Total to be paid this month</th>
                        <th class="px-4 py-3 summit-money">Overall remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['staff_shares'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['staff']->name }}</td>
                            <td class="px-4 py-3">{{ $this->percent($row['distribution_rate']) }}</td>
                            <td class="px-4 py-3">{{ $this->percent($row['commission_rate']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['previous_balance']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['monthly_commission_to_be_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['advance_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['payout_paid'] + $row['paid_amount']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['already_paid_this_month']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['total_to_be_paid_this_month']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['remaining_balance']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3" colspan="10">No active commission staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
