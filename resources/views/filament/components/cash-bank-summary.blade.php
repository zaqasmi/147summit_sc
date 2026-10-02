@if (auth()->user()?->isAdmin())
@php
    $cashBankSummary = \App\Models\BankTransaction::summary($asOf ?? today());
    $cashBankMoney = fn ($amount): string => 'Rs '.number_format((float) $amount, 2);
@endphp
    <div class="summit-panel bg-white p-4 dark:bg-gray-900">
        <div class="font-semibold">Cash and bank balances</div>
        <div class="text-sm text-gray-600 dark:text-gray-400">Recorded transactions as of {{ \Illuminate\Support\Carbon::parse($cashBankSummary['as_of'])->format('d M Y') }}</div>
        <dl class="summit-description-list mt-4 md:grid md:grid-cols-2 md:gap-x-8">
            <div><dt>Cash to be deposited in bank</dt><dd class="summit-money font-semibold">{{ $cashBankMoney($cashBankSummary['collection_cash_pending_deposit']) }}</dd></div>
            <div><dt>RF Account balance</dt><dd class="summit-money font-semibold">{{ $cashBankMoney($cashBankSummary['account_balances']['rf_account']) }}</dd></div>
            <div><dt>Saving Account balance</dt><dd class="summit-money font-semibold">{{ $cashBankMoney($cashBankSummary['account_balances']['saving_account']) }}</dd></div>
            <div><dt>Collection received through daily closings</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['cash_collected_from_closings']) }}</dd></div>
            <div><dt>Cash receipts outside daily closings</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['cash_receipts_outside_closings']) }}</dd></div>
            <div><dt>Pending cash adjustments (net)</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['pending_cash_adjustment_in'] - $cashBankSummary['pending_cash_adjustment_out']) }}</dd></div>
            <div><dt>Cash payments and construction saved</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['cash_outflow_pending_deductions']) }}</dd></div>
            <div><dt>Collection deposited in RF Account</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['deposits_by_account']['rf_account']) }}</dd></div>
            <div><dt>Collection deposited in Saving Account</dt><dd class="summit-money">{{ $cashBankMoney($cashBankSummary['deposits_by_account']['saving_account']) }}</dd></div>
        </dl>
    </div>
@endif
