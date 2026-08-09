<x-filament-panels::page>
    @php($report = $this->report())
    @php($tableNumbers = $report['table_numbers'] ?? [1, 2, 3, 4])

    <div class="summit-report-toolbar">
        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
            Report year
            <input
                type="number"
                min="2000"
                max="2100"
                wire:model.live="year"
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
            ['label' => 'Sales after dues', 'amount' => $report['sales_total'], 'tone' => 'green'],
            ['label' => 'Expenses', 'amount' => $report['expense_total'], 'tone' => 'rose'],
            ['label' => 'Rent', 'amount' => $report['rent_expense_total'], 'tone' => 'rose'],
            ['label' => 'Net profit', 'amount' => $report['net_profit'], 'tone' => 'teal'],
            ['label' => 'Salesmen commission', 'amount' => $report['commission_estimate'], 'tone' => 'amber'],
            ['label' => 'Owner profit after staff', 'amount' => $report['owner_profit_after_staff_share'], 'tone' => 'green'],
            ['label' => 'Dues outstanding', 'amount' => $report['dues']['balance_total'], 'tone' => 'rose'],
        ] as $stat)
            <div class="summit-stat-card" data-tone="{{ $stat['tone'] }}">
                <div class="summit-stat-label">{{ $stat['label'] }}</div>
                <div class="summit-stat-value">{{ $this->money($stat['amount']) }}</div>
            </div>
        @endforeach
    </div>

    <div class="summit-panel bg-white p-4 dark:bg-gray-900">
        <div class="font-semibold text-gray-950 dark:text-white">{{ $report['year']->format('Y') }} totals</div>
        <dl class="summit-description-list mt-4 md:grid md:grid-cols-2 md:gap-x-8">
            <div><dt>Cash receipts</dt><dd class="summit-money font-semibold">{{ $this->money($report['cash_collected']) }}</dd></div>
            <div><dt>Daily expenses</dt><dd class="summit-money font-semibold">{{ $this->money($report['daily_expense_total']) }}</dd></div>
            <div><dt>Rent expenses</dt><dd class="summit-money font-semibold">{{ $this->money($report['rent_expense_total']) }}</dd></div>
            <div><dt>Staff paid deducted</dt><dd class="summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</dd></div>
            <div><dt>Salesmen commission</dt><dd class="summit-money font-semibold">{{ $this->money($report['commission_estimate']) }}</dd></div>
            <div><dt>Construction deductions</dt><dd class="summit-money font-semibold">{{ $this->money($report['construction_deductions']['deducted_total']) }}</dd></div>
            <div><dt>Received in other account</dt><dd class="summit-money font-semibold">{{ $this->money($report['construction_deductions']['received_total']) }}</dd></div>
            <div><dt>Construction balance</dt><dd class="summit-money font-semibold">{{ $this->money($report['construction_deductions']['balance_total']) }}</dd></div>
            <div><dt>New dues this year</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_added']) }}</dd></div>
            <div><dt>Dues recovered this year</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_recovered']) }}</dd></div>
            <div><dt>Net dues change</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_net_change']) }}</dd></div>
            <div><dt>Total dues till now</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues']['balance_total']) }}</dd></div>
        </dl>
    </div>

    <div class="summit-panel bg-white p-4 dark:bg-gray-900">
        <div class="font-semibold text-gray-950 dark:text-white">Capital and liabilities</div>
        <dl class="summit-description-list mt-4 md:grid md:grid-cols-2 md:gap-x-8">
            <div><dt>Capital installments paid this year</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital_installments_paid']) }}</dd></div>
            <div><dt>Total capital added</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_added']) }}</dd></div>
            <div><dt>Capital adjustments</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_reduced']) }}</dd></div>
            <div><dt>Capital to recover</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_invested']) }}</dd></div>
            <div><dt>Recovered from profit</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_recovered']) }}</dd></div>
            <div><dt>Still left to recover</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_remaining']) }}</dd></div>
            <div><dt>Capital liability total</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['principal_total']) }}</dd></div>
            <div><dt>Liability paid to date</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['paid_to_date']) }}</dd></div>
            <div><dt>Liability balance left</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['balance_total']) }}</dd></div>
        </dl>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">Monthly distribution</div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        @foreach ($tableNumbers as $tableNumber)
                            <th class="px-4 py-3 summit-money">Table {{ $tableNumber }}</th>
                        @endforeach
                        <th class="px-4 py-3 summit-money">Gross sale</th>
                        <th class="px-4 py-3 summit-money">Net customer dues</th>
                        <th class="px-4 py-3 summit-money">Sale after dues</th>
                        <th class="px-4 py-3 summit-money">Daily expense</th>
                        <th class="px-4 py-3 summit-money">Rent</th>
                        <th class="px-4 py-3 summit-money">Total expense</th>
                        <th class="px-4 py-3 summit-money">Total collection</th>
                        <th class="px-4 py-3 summit-money">Collection after rent</th>
                        <th class="px-4 py-3 summit-money">Commission</th>
                        <th class="px-4 py-3 summit-money">Staff paid deducted</th>
                        <th class="px-4 py-3 summit-money">Advance carry</th>
                        <th class="px-4 py-3 summit-money">To be paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['months'] as $month)
                        <tr>
                            <td class="px-4 py-3">{{ $month['month']->format('F') }}</td>
                            @foreach ($tableNumbers as $tableNumber)
                                <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['table_sales_by_number'][$tableNumber] ?? 0) }}</td>
                            @endforeach
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['gross_sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['dues_net_change']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['daily_expense_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['rent_expense_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['expense_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['cash_collected']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['collection_after_rent']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['commission_estimate']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['staff_paid_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['staff_advance_carry_in']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['staff_distribution_to_be_paid']) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="px-4 py-3 font-semibold">Year total</td>
                        @foreach ($tableNumbers as $tableNumber)
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money(collect($report['months'])->sum(fn (array $month): float => (float) ($month['table_sales_by_number'][$tableNumber] ?? 0))) }}</td>
                        @endforeach
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['gross_sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['dues_net_change']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['sales_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['daily_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['rent_expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['expense_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['cash_collected']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['collection_after_rent']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['commission_estimate']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_advance_carry_forward']) }}</td>
                        <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($report['staff_distribution_to_be_paid_total']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">Monthly dues</div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3 summit-money">Sales</th>
                        <th class="px-4 py-3 summit-money">New dues</th>
                        <th class="px-4 py-3 summit-money">Recovered</th>
                        <th class="px-4 py-3 summit-money">Net dues</th>
                        <th class="px-4 py-3 summit-money">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['months'] as $month)
                        <tr>
                            <td class="px-4 py-3">{{ $month['month']->format('F') }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['sales_total']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['dues_added']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['dues_recovered']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['dues_net_change']) }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($month['dues']['balance_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
