<?php

namespace Tests\Feature;

use App\Filament\Resources\CashDeposits\Pages\CreateCashDeposit;
use App\Filament\Resources\CashDeposits\Pages\EditCashDeposit;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Models\BankTransaction;
use App\Models\CapitalLiability;
use App\Models\CapitalLiabilityPayment;
use App\Models\CashDeposit;
use App\Models\CustomerDue;
use App\Models\CustomerDuePayment;
use App\Models\Expense;
use App\Models\GameSession;
use App\Models\MonthlyClosing;
use App\Models\Payment;
use App\Models\SnookerTable;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_liabilities_and_rent_update_the_selected_bank_without_duplicates(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 30));
        foreach (BankTransaction::accountOptions() as $account => $label) {
            BankTransaction::create(['transaction_date' => today(), 'bank_account' => $account, 'type' => 'owner_deposit', 'amount' => 1000]);
        }
        $expense = Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Power', 'amount' => 100, 'paid_from' => 'saving_account']);
        $liability = CapitalLiability::create(['start_date' => today(), 'title' => 'Loan', 'category' => 'Loan', 'principal_amount' => 500]);
        $payment = CapitalLiabilityPayment::create(['capital_liability_id' => $liability->id, 'payment_date' => today(), 'amount' => 200, 'paid_from' => 'saving_account']);
        $closing = MonthlyClosing::create(['month' => today()->startOfMonth(), 'rent_paid_amount' => 300, 'rent_paid_from' => 'saving_account']);
        $this->assertSame(['rf_account' => 1000.0, 'saving_account' => 400.0], BankTransaction::summary()['account_balances']);
        $this->assertSame(200.0, app(ReportService::class)->daily(today())['capital_installments_paid_from_business']);
        $this->assertSame(0.0, BankTransaction::summary()['cash_outflow_pending_deductions']);

        $expense->update(['paid_from' => 'bank']);
        $payment->update(['paid_from' => 'bank']);
        $closing->update(['rent_paid_from' => 'bank']);
        $this->assertSame(['rf_account' => 400.0, 'saving_account' => 1000.0], BankTransaction::summary()['account_balances']);
        $this->assertSame(5, BankTransaction::count());
        $expense->update(['paid_from' => 'cash']);
        $payment->update(['paid_from' => 'owner']);
        $closing->update(['rent_paid_from' => 'cash']);
        $this->assertSame(['rf_account' => 1000.0, 'saving_account' => 1000.0], BankTransaction::summary()['account_balances']);
        $this->assertSame(400.0, BankTransaction::summary()['cash_outflow_pending_deductions']);
        $this->assertNotNull($payment->ownerCapital()->first());
        $this->travelBack();
    }

    public function test_daily_closing_deducts_cash_expenses_once_and_adds_manually_recovered_dues(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'accounts@example.test', 'role' => 'admin', 'password' => 'password']));
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create(['number' => $number, 'name' => 'Table '.$number, 'hourly_rate' => 10]);
        }
        $due = CustomerDue::create(['customer_name' => 'Customer', 'opening_balance' => 500]);
        Livewire::test(CreateCashDeposit::class)
            ->fillForm([
                'deposit_date' => today()->toDateString(), 'closing_source' => 'manual', 'manual_table_1_sale' => 1000,
                'expenses' => [
                    ['category' => 'Utilities', 'description' => 'Power', 'amount' => 200, 'paid_from' => 'cash'],
                    ['category' => 'Supplies', 'description' => 'Chalk', 'amount' => 50, 'paid_from' => 'cash'],
                ],
                'customerDuePayments' => [
                    ['customer_due_id' => $due->id, 'amount' => 100, 'discount_amount' => 0, 'payment_method' => 'cash'],
                ],
                'amount_collected_from_staff' => 850,
            ])
            ->call('create')->assertHasNoFormErrors();
        $closing = CashDeposit::firstOrFail();
        $this->assertSame(250.0, (float) $closing->manual_expense_total);
        $this->assertSame(850.0, (float) $closing->cash_collected_from_counter);
        $this->assertSame('cash', $closing->expenses()->where('description', 'Power')->firstOrFail()->paid_from);
        $this->assertSame(400.0, (float) $due->refresh()->balance_due);
        $this->assertSame(['rf_account' => 0.0, 'saving_account' => 0.0], BankTransaction::summary()['account_balances']);
        $this->assertSame(850.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(850.0, app(ReportService::class)->daily(today())['counter_cash_expected']);
        Expense::create(['expense_date' => today(), 'category' => 'Supplies', 'description' => 'Separate cash expense', 'amount' => 20, 'paid_from' => 'cash']);
        Expense::create(['expense_date' => today(), 'category' => 'Utilities', 'description' => 'Separate bank expense', 'amount' => 10, 'paid_from' => 'saving_account']);
        $this->assertSame(830.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(-10.0, BankTransaction::summary()['account_balances']['saving_account']);
    }

    public function test_game_and_due_receipts_follow_account_changes_and_deletion(): void
    {
        $table = SnookerTable::create(['number' => 1, 'name' => 'Table 1', 'hourly_rate' => 10]);
        $session = GameSession::create(['snooker_table_id' => $table->id, 'game_type' => 'one_to_one', 'status' => 'active', 'started_at' => now()]);
        $payment = Payment::create(['game_session_id' => $session->id, 'payment_date' => today(), 'payment_method' => 'saving_account', 'amount' => 100]);
        $this->assertSame(['rf_account' => 0.0, 'saving_account' => 100.0], BankTransaction::summary()['account_balances']);
        $this->assertSame(0.0, app(ReportService::class)->daily(today())['cash_collected']);
        $payment->update(['payment_method' => 'bank']);
        $this->assertSame(['rf_account' => 100.0, 'saving_account' => 0.0], BankTransaction::summary()['account_balances']);
        $payment->update(['payment_method' => 'cash']);
        $this->assertSame(0, BankTransaction::where('source_type', BankTransaction::SOURCE_PAYMENT)->count());
        $due = CustomerDue::create(['customer_name' => 'Customer', 'opening_balance' => 200]);
        $receipt = CustomerDuePayment::create(['customer_due_id' => $due->id, 'payment_date' => today(), 'payment_method' => 'saving_account', 'amount' => 50]);
        $this->assertSame(50.0, BankTransaction::summary()['account_balances']['saving_account']);
        $receipt->delete();
        $payment->delete();
        $this->assertSame(0, BankTransaction::count());
    }

    public function test_game_closing_counts_bank_receipts_as_sales_and_keeps_them_out_of_cash(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'game-accounts@example.test', 'role' => 'admin', 'password' => 'password']));
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create(['number' => $number, 'name' => 'Table '.$number, 'hourly_rate' => 10]);
        }
        $session = GameSession::create([
            'snooker_table_id' => SnookerTable::firstOrFail()->id, 'game_type' => 'one_to_one', 'status' => 'completed',
            'started_at' => now()->subHour(), 'ended_at' => now(), 'checked_out_at' => now(), 'frames_played' => 1, 'frame_fee' => 200,
        ]);
        $participant = $session->participants()->create(['player_name_snapshot' => 'Player', 'team' => 'solo', 'is_loser' => true]);
        $participant->payments()->create(['payment_date' => today(), 'payment_method' => 'saving_account', 'amount' => 100]);
        $participant->payments()->create(['payment_date' => today(), 'payment_method' => 'cash', 'amount' => 100]);
        Livewire::test(CreateCashDeposit::class)
            ->fillForm(['deposit_date' => today()->toDateString(), 'closing_source' => 'game_sessions', 'amount_collected_from_staff' => 100])
            ->call('create')->assertHasNoFormErrors();
        $this->assertSame(100.0, (float) CashDeposit::firstOrFail()->cash_collected_from_counter);
        $this->assertSame(200.0, app(ReportService::class)->daily(today())['sales_total']);
        $this->assertSame(100.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(100.0, BankTransaction::summary()['account_balances']['saving_account']);
        $session->delete();
        $this->assertSame(0.0, BankTransaction::summary()['account_balances']['saving_account']);
    }

    public function test_new_game_payments_are_cash_only_and_historical_payment_sources_keep_their_ids(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'manual-payments@example.test', 'role' => 'admin', 'password' => 'password']));
        $table = SnookerTable::create(['number' => 1, 'name' => 'Table 1', 'hourly_rate' => 10]);
        $session = GameSession::create(['snooker_table_id' => $table->id, 'game_type' => 'one_to_one', 'status' => 'active', 'started_at' => now()]);
        Livewire::test(CreatePayment::class)->fillForm([
            'game_session_id' => $session->id, 'payment_date' => today()->toDateString(), 'amount' => 100,
        ])->assertSee('Game payments are collected manually in cash.')
            ->call('create')->assertHasNoFormErrors();
        $this->assertSame('cash', Payment::firstOrFail()->payment_method);
        $this->assertSame(0, BankTransaction::count());
        Livewire::test(CreatePayment::class)->fillForm([
            'game_session_id' => $session->id, 'payment_date' => today()->toDateString(), 'amount' => 100, 'payment_method' => 'bank',
        ])->call('create')->assertHasFormErrors(['payment_method' => 'in']);

        $historical = Payment::create(['game_session_id' => $session->id, 'payment_date' => today(), 'payment_method' => 'bank', 'amount' => 50]);
        $ledger = BankTransaction::where('source_type', BankTransaction::SOURCE_PAYMENT)->where('source_id', $historical->id)->firstOrFail();
        Livewire::test(EditPayment::class, ['record' => $historical->id])
            ->fillForm(['notes' => 'Updated note'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('bank', $historical->refresh()->payment_method);
        $this->assertSame($ledger->id, BankTransaction::where('source_type', BankTransaction::SOURCE_PAYMENT)->where('source_id', $historical->id)->firstOrFail()->id);
    }

    public function test_editing_a_historical_closing_preserves_previously_recorded_bank_sources(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'historical-closing@example.test', 'role' => 'admin', 'password' => 'password']));
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create(['number' => $number, 'name' => 'Table '.$number, 'hourly_rate' => 10]);
        }
        $closing = CashDeposit::create(['deposit_date' => today(), 'closing_source' => 'manual', 'manual_table_1_sale' => 1000, 'manual_expense_total' => 100, 'dues_recovered' => 50, 'amount_collected_from_staff' => 1000]);
        $expense = Expense::create(['cash_deposit_id' => $closing->id, 'expense_date' => today(), 'category' => 'Utilities', 'description' => 'Legacy expense', 'amount' => 100, 'paid_from' => 'bank']);
        $expenseLedgerId = $expense->bankTransaction()->firstOrFail()->id;
        $due = CustomerDue::create(['customer_name' => 'Customer', 'opening_balance' => 200]);
        $receipt = CustomerDuePayment::create(['cash_deposit_id' => $closing->id, 'customer_due_id' => $due->id, 'payment_date' => today(), 'payment_method' => 'bank', 'amount' => 50]);
        $receiptLedgerId = BankTransaction::where('source_type', BankTransaction::SOURCE_CUSTOMER_DUE_PAYMENT)->where('source_id', $receipt->id)->firstOrFail()->id;
        Livewire::test(EditCashDeposit::class, ['record' => $closing->id])
            ->fillForm(['notes' => 'Updated historical closing'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('bank', $expense->refresh()->paid_from);
        $this->assertSame('bank', $receipt->refresh()->payment_method);
        $this->assertSame($expenseLedgerId, $expense->bankTransaction()->firstOrFail()->id);
        $this->assertSame($receiptLedgerId, BankTransaction::where('source_type', BankTransaction::SOURCE_CUSTOMER_DUE_PAYMENT)->where('source_id', $receipt->id)->firstOrFail()->id);
    }
}
