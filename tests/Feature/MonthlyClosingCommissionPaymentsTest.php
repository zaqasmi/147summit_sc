<?php

namespace Tests\Feature;

use App\Filament\Pages\MonthlyReport;
use App\Filament\Resources\MonthlyClosings\Pages\CreateMonthlyClosing;
use App\Filament\Resources\MonthlyClosings\Pages\EditMonthlyClosing;
use App\Models\BankTransaction;
use App\Models\CashDeposit;
use App\Models\MonthlyClosing;
use App\Models\MonthlyCommission;
use App\Models\SnookerTable;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyClosingCommissionPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_creation_and_edits_preserve_dues_and_carry_overpayments_forward(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10));
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'closing@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        SnookerTable::create(['number' => 1, 'name' => 'Table 1', 'hourly_rate' => 10]);
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        CashDeposit::create([
            'deposit_date' => '2026-08-01', 'staff_id' => $staff->id, 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'manual_expense_total' => 200,
            'cash_collected_from_counter' => 800, 'amount_collected_from_staff' => 800,
        ]);
        StaffTransaction::create([
            'staff_id' => $staff->id, 'transaction_date' => '2026-08-05', 'type' => 'advance',
            'paid_from' => 'cash', 'amount' => 50,
        ]);
        $form = [
            'month' => '2026-08-01', 'status' => 'draft', 'rent_total' => 0, 'rent_paid_amount' => 0,
            'rent_paid_from' => 'bank', 'construction_deduction_amount' => 0, 'construction_received_amount' => 0,
            'commission_paid_overrides' => [$staff->id => 75],
            'commission_payment_sources' => [$staff->id => 'bank'],
        ];

        Livewire::test(CreateMonthlyClosing::class)
            ->fillForm($form)
            ->assertSee('Advance paid Rs 50.00')
            ->assertSee('Remaining due Rs 75.00')
            ->call('create')
            ->assertHasNoFormErrors();
        $closing = MonthlyClosing::forMonth('2026-08');
        $this->assertNotNull($closing);
        $commission = MonthlyCommission::where('staff_id', $staff->id)->whereDate('month', '2026-08-01')->firstOrFail();
        $this->assertSame(200.0, (float) $commission->commission_amount);
        $this->assertSame(75.0, (float) $commission->balance_due);
        $debit = BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->where('source_id', $commission->id)->firstOrFail();
        $this->assertSame('rf_account', $debit->bank_account);
        $this->assertSame(75.0, (float) $debit->amount);
        $this->assertSame(-75.0, BankTransaction::summary()['account_balances']['rf_account']);
        $this->assertSame(50.0, BankTransaction::summary()['cash_staff_payments_pending_deduction']);
        $later = app(ReportService::class)->generateMonthlyCommission($staff, '2026-09-01');
        $this->assertSame(75.0, (float) $later->carried_forward_from_previous);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->assertFormSet(['commission_paid_overrides' => [$staff->id => 75]])
            ->fillForm(['commission_paid_overrides' => [$staff->id => 250], 'commission_payment_sources' => [$staff->id => 'saving_account']])
            ->assertSee('Advance carried forward Rs 100.00')
            ->call('save')->assertHasNoFormErrors()
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(250.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame(-100.0, (float) $commission->balance_due);
        $this->assertSame(-100.0, (float) $later->refresh()->carried_forward_from_previous);
        $this->assertSame(-100.0, (float) $later->balance_due);
        $this->assertSame('saving_account', $debit->refresh()->bank_account);
        $this->assertSame(250.0, (float) $debit->amount);
        $this->assertSame(1, BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->count());
        $this->assertSame(['rf_account' => 0.0, 'saving_account' => -250.0], BankTransaction::summary()['account_balances']);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->fillForm(['commission_paid_overrides' => [$staff->id => -1]])
            ->call('save')->assertHasFormErrors(['commission_paid_overrides.'.$staff->id => 'min']);
        $this->assertSame(250.0, (float) $commission->refresh()->paid_amount);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->callAction('commissionPayments', ['staff_'.$staff->id => 25, 'source_'.$staff->id => 'cash'])
            ->assertHasNoActionErrors()
            ->assertFormSet(['commission_paid_overrides' => [$staff->id => 25]])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(25.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame(125.0, (float) $commission->balance_due);
        $this->assertSame(125.0, (float) $later->refresh()->balance_due);
        $this->assertSame(0, BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->count());
        $this->assertSame(75.0, BankTransaction::summary()['cash_staff_payments_pending_deduction']);
        $this->assertSame(725.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame('cash', app(ReportService::class)->generateMonthlyCommission($staff, '2026-08')->paid_from);

        Livewire::test(CreateMonthlyClosing::class)
            ->set('data.month', '2026-08-01')
            ->assertSet('data.commission_paid_overrides.'.$staff->id, 25.0)
            ->set('data.month', '2026-10-01')
            ->assertSet('data.commission_paid_overrides.'.$staff->id, 0.0);
        $this->travelBack();
    }

    public function test_monthly_report_saves_payment_source_and_rejects_unknown_accounts(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10));
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'sources@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        $staff = Staff::create(['name' => 'Staff', 'commission_rate' => 25]);
        MonthlyClosing::create([
            'month' => '2026-08-01', 'status' => 'draft', 'rent_total' => 0,
            'rent_paid_amount' => 0, 'construction_deduction_amount' => 0,
        ]);
        $page = Livewire::test(MonthlyReport::class)
            ->set('month', '2026-08')
            ->set('staffCommissionPayments.'.$staff->id, 100)
            ->set('staffCommissionPaymentSources.'.$staff->id, 'saving_account')
            ->call('saveMonthlyClosingDraft')->assertHasNoErrors();
        $commission = MonthlyCommission::where('staff_id', $staff->id)->whereDate('month', '2026-08-01')->firstOrFail();
        $this->assertSame('saving_account', $commission->paid_from);
        $this->assertSame(-100.0, BankTransaction::summary()['account_balances']['saving_account']);
        $page->set('staffCommissionPaymentSources.'.$staff->id, 'invalid')
            ->call('saveMonthlyClosingDraft')
            ->assertHasErrors(['staffCommissionPaymentSources.'.$staff->id]);
        $this->assertSame('saving_account', $commission->refresh()->paid_from);
        $commission->delete();
        $this->assertSame(0, BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->count());
        $this->travelBack();
    }

    public function test_staff_advances_use_the_selected_bank_and_bank_balances_include_deposits(): void
    {
        BankTransaction::create(['transaction_date' => today(), 'type' => 'owner_deposit', 'amount' => 500, 'bank_account' => 'rf_account']);
        BankTransaction::create(['transaction_date' => today(), 'type' => 'owner_deposit', 'amount' => 300, 'bank_account' => 'saving_account']);
        $staff = Staff::create(['name' => 'Staff', 'commission_rate' => 25]);
        $advance = StaffTransaction::create([
            'staff_id' => $staff->id, 'transaction_date' => today(), 'type' => 'advance',
            'paid_from' => 'saving_account', 'amount' => 50,
        ]);
        $this->assertSame(['rf_account' => 500.0, 'saving_account' => 250.0], BankTransaction::summary()['account_balances']);
        $advance->update(['paid_from' => 'bank']);
        $this->assertSame(['rf_account' => 450.0, 'saving_account' => 300.0], BankTransaction::summary()['account_balances']);
        $advance->update(['paid_from' => 'cash']);
        $this->assertSame(['rf_account' => 500.0, 'saving_account' => 300.0], BankTransaction::summary()['account_balances']);
    }
}
