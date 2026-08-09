<x-filament-panels::page>
    @php($report = $this->report())
    @php($commission = $report['staff_commission_totals'])
    @php($closing = $report['monthly_closing'])
    @php($monthClosed = $this->isMonthClosed())
    @php($canManageMonthlyClosing = $this->canManageMonthlyClosing())
    @php($tableNumbers = $report['table_numbers'] ?? [1, 2, 3, 4])
    @php($rentSplitDifference = round((float) $closing['rent_total'] - (float) $closing['rent_paid_amount'] - (float) $closing['construction_deduction_amount'], 2))

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
            onclick="window.print()"
            class="summit-print-button"
        >
            Print report
        </button>
    </div>

    <div class="summit-stat-grid">
        @foreach ([
            ['label' => 'Overall commission rate', 'value' => $this->percent($report['overall_commission_rate']), 'tone' => 'amber'],
            ['label' => 'Cash collected', 'value' => $this->money($report['cash_collected']), 'tone' => 'green'],
            ['label' => 'Monthly rent', 'value' => $this->money($report['rent_expense_total']), 'tone' => 'rose'],
            ['label' => 'Distribution base after rent', 'value' => $this->money($report['commission_distribution_base']), 'tone' => 'teal'],
            ['label' => 'Monthly commission to be paid', 'value' => $this->money($commission['monthly_commission_to_be_paid']), 'tone' => 'teal'],
            ['label' => 'Previous advance balance', 'value' => $this->money($report['staff_advance_carry_in']), 'tone' => 'amber'],
            ['label' => 'Staff paid deducted', 'value' => $this->money($report['staff_paid_total']), 'tone' => 'green'],
            ['label' => 'Net payable / carry forward', 'value' => $this->money($report['staff_distribution_to_be_paid']), 'tone' => 'green'],
        ] as $stat)
            <div class="summit-stat-card" data-tone="{{ $stat['tone'] }}">
                <div class="summit-stat-label">{{ $stat['label'] }}</div>
                <div class="summit-stat-value">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="summit-panel bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 pb-3 dark:border-gray-800">
            <div>
                <div class="font-semibold text-gray-950 dark:text-white">Monthly closing</div>
                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Full rent for distribution, actual rent paid, construction recovery, and final commission snapshot.
                </div>
            </div>
            <div class="rounded-md px-3 py-1 text-sm font-semibold {{ $monthClosed ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200' }}">
                {{ $monthClosed ? 'Closed' : 'Draft' }}
            </div>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Total rent
                <input type="number" min="0" step="0.01" wire:model.live="rentTotal" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Rent paid
                <input type="number" min="0" step="0.01" wire:model.live="rentPaidAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Paid from
                <select wire:model.live="rentPaidFrom" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input">
                    <option value="bank">Bank</option>
                    <option value="cash">Cash</option>
                    <option value="other_account">Other account</option>
                </select>
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Construction deduction
                <input type="number" min="0" step="0.01" wire:model.live="constructionDeductionAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Saved in other account
                <input type="number" min="0" step="0.01" wire:model.live="constructionReceivedAmount" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Other account name
                <input type="text" wire:model.live="constructionAccountName" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
            <label class="flex items-center gap-2 pt-6 text-sm font-semibold text-gray-700 dark:text-gray-200">
                <input type="checkbox" wire:model.live="liabilitiesVerified" @disabled($monthClosed || ! $canManageMonthlyClosing) class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                Liabilities paid and verified
            </label>
            <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                Closing notes
                <input type="text" wire:model.live="closingNotes" @disabled($monthClosed || ! $canManageMonthlyClosing) class="summit-date-input" />
            </label>
        </div>

        <dl class="summit-description-list mt-4 md:grid md:grid-cols-2 md:gap-x-8 xl:grid-cols-4">
            <div><dt>Rent split difference</dt><dd class="summit-money font-semibold">{{ $this->money($rentSplitDifference) }}</dd></div>
            <div><dt>Construction recovery balance</dt><dd class="summit-money font-semibold">{{ $this->money($closing['construction_balance']) }}</dd></div>
            <div><dt>Total construction deducted</dt><dd class="summit-money font-semibold">{{ $this->money($report['construction_deductions']['deducted_total']) }}</dd></div>
            <div><dt>Total saved other account</dt><dd class="summit-money font-semibold">{{ $this->money($report['construction_deductions']['received_total']) }}</dd></div>
            <div><dt>Liabilities paid this month</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital_installments_paid']) }}</dd></div>
            <div><dt>Rent source</dt><dd class="font-semibold">{{ ucfirst(str_replace('_', ' ', $closing['source'])) }}</dd></div>
        </dl>

        @if ($canManageMonthlyClosing)
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" wire:click="saveMonthlyClosingDraft" @disabled($monthClosed) class="summit-print-button">
                    Save draft
                </button>
                <button type="button" wire:click="closeMonth" @disabled($monthClosed) class="summit-print-button">
                    Close month
                </button>
            </div>
        @endif
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
                        <th class="px-4 py-3 summit-money">Commission</th>
                        <th class="px-4 py-3 summit-money">Staff paid deducted</th>
                        <th class="px-4 py-3 summit-money">Paid deducted</th>
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
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['already_paid_this_month']) }}</td>
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
                        <td class="px-4 py-3">Monthly commission to be paid</td>
                        <td class="px-4 py-3">{{ $this->money($report['commission_distribution_base']) }} x {{ $this->percent($report['overall_commission_rate']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($commission['monthly_commission_to_be_paid']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Previous advance balance</td>
                        <td class="px-4 py-3">Negative amount carried from previous months</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_advance_carry_in']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Staff paid deducted this month</td>
                        <td class="px-4 py-3">Advances and payouts deducted from this month commission</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3">Net payable / carry forward</td>
                        <td class="px-4 py-3">Monthly commission + previous advance balance - staff paid deducted</td>
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
                        <th class="px-4 py-3 summit-money">Monthly commission to be paid</th>
                        <th class="px-4 py-3 summit-money">Advance</th>
                        <th class="px-4 py-3 summit-money">Paid</th>
                        <th class="px-4 py-3 summit-money">Already paid this month</th>
                        <th class="px-4 py-3 summit-money">Total to be paid this month</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['staff_shares'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['staff']->name }}</td>
                            <td class="px-4 py-3">{{ $this->percent($row['distribution_rate']) }}</td>
                            <td class="px-4 py-3">{{ $this->percent($row['commission_rate']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['monthly_commission_to_be_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['advance_paid']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['payout_paid'] + $row['paid_amount']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['already_paid_this_month']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['total_to_be_paid_this_month']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-3" colspan="8">No active staff found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
