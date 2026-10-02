<?php

namespace Tests\Feature;

use App\Filament\Resources\BankTransactions\Pages\CreateBankTransaction;
use App\Filament\Resources\BankTransactions\Pages\ListBankTransactions;
use App\Filament\Widgets\CashToDepositSummary;
use App\Models\BankTransaction;
use App\Models\CapitalLiability;
use App\Models\CapitalLiabilityPayment;
use App\Models\CashDeposit;
use App\Models\CustomerDue;
use App\Models\CustomerDuePayment;
use App\Models\Expense;
use App\Models\MonthlyClosing;
use App\Models\MonthlyCommission;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashToDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_to_deposit_includes_receipts_and_cash_payments_and_deposits_to_both_accounts(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 30));
        $staff = Staff::create(['name' => 'Staff', 'commission_rate' => 25]);
        CashDeposit::create(['deposit_date' => today(), 'closing_source' => 'manual', 'amount_collected_from_staff' => 1000]);
        $due = CustomerDue::create(['customer_name' => 'Customer', 'opening_balance' => 100]);
        CustomerDuePayment::create(['customer_due_id' => $due->id, 'payment_date' => today(), 'amount' => 100]);
        BankTransaction::create(['transaction_date' => today(), 'type' => 'cash_pending_adjustment_in', 'amount' => 10]);
        foreach (['cash' => 50, 'bank' => 200] as $source => $amount) {
            StaffTransaction::create(['staff_id' => $staff->id, 'transaction_date' => today(), 'type' => 'advance', 'paid_from' => $source, 'amount' => $amount]);
        }
        MonthlyCommission::where('staff_id', $staff->id)->firstOrFail()->update(['paid_amount' => 70, 'paid_from' => 'cash']);
        foreach (['cash' => 40, 'saving_account' => 30] as $source => $amount) {
            Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Power', 'amount' => $amount, 'paid_from' => $source]);
        }
        $liability = CapitalLiability::create(['start_date' => today(), 'title' => 'Loan', 'category' => 'Loan', 'principal_amount' => 500]);
        foreach (['cash' => 60, 'bank' => 20] as $source => $amount) {
            CapitalLiabilityPayment::create(['capital_liability_id' => $liability->id, 'payment_date' => today(), 'amount' => $amount, 'paid_from' => $source]);
        }
        MonthlyClosing::create(['month' => today()->startOfMonth(), 'rent_paid_amount' => 80, 'rent_paid_from' => 'cash', 'construction_received_amount' => 30]);
        $this->assertSame(780.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(100.0, BankTransaction::summary()['cash_receipts_outside_closings']);
        foreach (['rf_account' => 100, 'saving_account' => 200] as $account => $amount) {
            BankTransaction::create(['transaction_date' => today(), 'type' => 'daily_collection_deposit', 'amount' => $amount, 'bank_account' => $account]);
        }
        $summary = BankTransaction::summary();
        $this->assertSame(480.0, $summary['collection_cash_pending_deposit']);
        $this->assertSame(['rf_account' => 100.0, 'saving_account' => 200.0], $summary['deposits_by_account']);
        $this->assertSame(['rf_account' => -120.0, 'saving_account' => 170.0], $summary['account_balances']);
        $this->travelBack();
    }

    public function test_game_cash_receipts_are_replaced_by_the_daily_closing_without_double_counting(): void
    {
        // Standalone cash receipts are permitted by the payments table.
        Payment::withoutEvents(fn () => Payment::create(['payment_date' => today(), 'payment_method' => 'cash', 'amount' => 500]));
        $this->assertSame(500.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $closing = CashDeposit::create(['deposit_date' => today(), 'amount_collected_from_staff' => 450]);
        $this->assertSame(450.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(0.0, BankTransaction::summary()['cash_receipts_outside_closings']);
        $closing->delete();
        $this->assertSame(500.0, BankTransaction::summary()['collection_cash_pending_deposit']);
    }

    public function test_deposit_actions_and_manual_forms_use_available_cash_on_the_selected_date(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'cash-deposit@example.test', 'role' => 'admin', 'password' => 'password']));
        CashDeposit::create(['deposit_date' => today(), 'amount_collected_from_staff' => 500]);
        $page = Livewire::test(ListBankTransactions::class)->assertSeeLivewire(CashToDepositSummary::class);
        $page->callAction('depositPendingCash', [
            'transaction_date' => today()->toDateString(), 'amount' => 200, 'bank_account' => 'saving_account',
        ])->assertHasNoActionErrors()->assertDispatched('cash-bank-balances-updated');
        $this->assertSame(300.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(200.0, BankTransaction::summary()['account_balances']['saving_account']);
        $page->callAction('depositPendingCash', [
            'transaction_date' => today()->subDay()->toDateString(), 'amount' => 100, 'bank_account' => 'rf_account',
        ])->assertHasActionErrors(['amount' => 'max']);
        Livewire::test(CreateBankTransaction::class)->fillForm([
            'transaction_date' => today()->toDateString(), 'entry_side' => 'credit', 'type' => 'daily_collection_deposit',
            'bank_account' => 'rf_account', 'amount' => 301,
        ])->call('create')->assertHasFormErrors(['amount' => 'max']);
        $this->assertSame(300.0, BankTransaction::summary()['collection_cash_pending_deposit']);
    }

    public function test_financial_resources_share_the_cash_and_bank_balance_widget(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'cash-widgets@example.test', 'role' => 'admin', 'password' => 'password']));
        foreach (['CashDeposits', 'Expenses', 'StaffTransactions', 'Payments', 'CapitalLiabilityPayments', 'MonthlyClosings', 'MonthlyCommissions', 'CustomerDues', 'CapitalLiabilities', 'OwnerCapitals'] as $resource) {
            Livewire::test('App\\Filament\\Resources\\'.$resource.'\\Pages\\List'.$resource)
                ->assertSeeLivewire(CashToDepositSummary::class);
        }
        Livewire::test(CashToDepositSummary::class)->assertSee('Cash to be deposited in bank')->assertSee('RF Account')->assertSee('Saving Account');
    }
}
