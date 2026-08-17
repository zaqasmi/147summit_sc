<x-filament-panels::page>
    @php($report = $this->report())

    <div class="summit-report-toolbar">
        <label class="grid gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
            Report date
            <input
                type="date"
                wire:model.live="date"
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
            ['label' => 'Table sales', 'amount' => $report['sales_total'], 'tone' => 'teal'],
            ['label' => 'Counter cash', 'amount' => $report['counter_cash_collected'], 'tone' => 'green'],
            ['label' => 'Dues recovered', 'amount' => $report['dues_recovered'], 'tone' => 'green'],
            ['label' => 'Dues discounted', 'amount' => $report['dues_discounted'], 'tone' => 'amber'],
            ['label' => 'Expenses', 'amount' => $report['expense_total'], 'tone' => 'rose'],
            ['label' => 'Staff paid', 'amount' => $report['staff_paid_total'], 'tone' => 'amber'],
            ['label' => 'Net cash profit', 'amount' => $report['net_cash_profit'], 'tone' => 'amber'],
            ['label' => 'Owner profit after staff', 'amount' => $report['owner_profit_after_staff_share'], 'tone' => 'teal'],
            ['label' => 'Dues outstanding', 'amount' => $report['dues']['balance_total'], 'tone' => 'rose'],
        ] as $stat)
            <div class="summit-stat-card" data-tone="{{ $stat['tone'] }}">
                <div class="summit-stat-label">{{ $stat['label'] }}</div>
                <div class="summit-stat-value">{{ $this->money($stat['amount']) }}</div>
            </div>
        @endforeach
    </div>

    <div class="summit-panel bg-white dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 font-semibold dark:border-gray-800">Table sales</div>
        <div class="overflow-x-auto">
            <table class="summit-table">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Table</th>
                        <th class="px-4 py-3">Sessions</th>
                        <th class="px-4 py-3">Frames</th>
                        <th class="px-4 py-3 summit-money">Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['table_sales'] as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['table']->name }}</td>
                            <td class="px-4 py-3">{{ $row['sessions'] }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $row['frames'] }}</td>
                            <td class="px-4 py-3 summit-money font-semibold">{{ $this->money($row['sales']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="summit-panel bg-white p-4 dark:bg-gray-900">
            <div class="font-semibold text-gray-950 dark:text-white">Cash closing</div>
            <dl class="summit-description-list mt-4">
                <div><dt>Closing source</dt><dd class="font-semibold">{{ $report['source_label'] }}</dd></div>
                <div><dt>Opening petty cash</dt><dd class="summit-money font-semibold">{{ $this->money($report['opening_petty_cash']) }}</dd></div>
                <div><dt>Cash receipts today</dt><dd class="summit-money font-semibold">{{ $this->money($report['cash_collected']) }}</dd></div>
                <div><dt>Counter cash expected</dt><dd class="summit-money font-semibold">{{ $this->money($report['counter_cash_expected']) }}</dd></div>
                <div><dt>Counter cash counted</dt><dd class="summit-money font-semibold">{{ $this->money($report['counter_cash_collected']) }}</dd></div>
                <div><dt>Expected amount to collect</dt><dd class="summit-money font-semibold">{{ $this->money($report['expected_owner_collection']) }}</dd></div>
                <div><dt>Collected from manager</dt><dd class="summit-money font-semibold">{{ $this->money($report['amount_collected_from_staff']) }}</dd></div>
                <div><dt>Petty cash kept</dt><dd class="summit-money font-semibold">{{ $this->money($report['petty_cash_kept']) }}</dd></div>
                <div><dt>Deposited to bank today</dt><dd class="summit-money font-semibold">{{ $this->money($report['bank_deposit_amount']) }}</dd></div>
            </dl>
        </div>

        <div class="summit-panel bg-white p-4 dark:bg-gray-900">
            <div class="font-semibold text-gray-950 dark:text-white">Other totals</div>
            <dl class="summit-description-list mt-4">
                <div><dt>Completed sessions</dt><dd class="font-semibold">{{ $report['sessions_count'] }}</dd></div>
                <div><dt>Total frames</dt><dd class="font-semibold">{{ $report['frames_count'] }}</dd></div>
                <div><dt>Add-on sales</dt><dd class="summit-money font-semibold">{{ $this->money($report['add_on_total']) }}</dd></div>
                <div><dt>Expense total</dt><dd class="summit-money font-semibold">{{ $this->money($report['expense_total']) }}</dd></div>
                <div><dt>New dues today</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_added']) }}</dd></div>
                <div><dt>Dues recovered today</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_recovered']) }}</dd></div>
                <div><dt>Dues discounted today</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_discounted']) }}</dd></div>
                <div><dt>Net dues change</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues_net_change']) }}</dd></div>
                <div><dt>Total dues till now</dt><dd class="summit-money font-semibold">{{ $this->money($report['dues']['balance_total']) }}</dd></div>
                <div><dt>Staff paid recorded</dt><dd class="summit-money font-semibold">{{ $this->money($report['staff_paid_total']) }}</dd></div>
                <div><dt>Staff share estimate</dt><dd class="summit-money font-semibold">{{ $this->money($report['staff_share_estimate']) }}</dd></div>
                <div><dt>Owner profit after staff share</dt><dd class="summit-money font-semibold">{{ $this->money($report['owner_profit_after_staff_share']) }}</dd></div>
                <div><dt>Cash after expenses</dt><dd class="summit-money font-semibold">{{ $this->money($report['net_cash_profit']) }}</dd></div>
            </dl>
        </div>
    </div>

    @if (auth()->user()?->isAdmin())
        <div class="summit-panel bg-white p-4 dark:bg-gray-900">
            <div class="font-semibold text-gray-950 dark:text-white">Capital and liabilities</div>
            <dl class="summit-description-list mt-4 md:grid md:grid-cols-2 md:gap-x-8">
                <div><dt>Capital installments paid today</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital_installments_paid']) }}</dd></div>
                <div><dt>Total capital added</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_added']) }}</dd></div>
                <div><dt>Capital adjustments</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_reduced']) }}</dd></div>
                <div><dt>Capital to recover</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_invested']) }}</dd></div>
                <div><dt>Owner profit to date</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['owner_profit_to_date']) }}</dd></div>
                <div><dt>Recovered from profit</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_recovered']) }}</dd></div>
                <div><dt>Still left to recover</dt><dd class="summit-money font-semibold">{{ $this->money($report['capital']['capital_remaining']) }}</dd></div>
                <div><dt>Capital liability total</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['principal_total']) }}</dd></div>
                <div><dt>Liability paid to date</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['paid_to_date']) }}</dd></div>
                <div><dt>Liability balance left</dt><dd class="summit-money font-semibold">{{ $this->money($report['liabilities']['balance_total']) }}</dd></div>
            </dl>
        </div>
    @endif
</x-filament-panels::page>
