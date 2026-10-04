<?php

namespace Tests\Feature;

use App\Filament\Resources\StaffTransactions\Pages\CreateStaffTransaction;
use App\Filament\Resources\StaffTransactions\Pages\EditStaffTransaction;
use App\Models\BankTransaction;
use App\Models\CashDeposit;
use App\Models\Expense;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\StaffCommissionPayment;
use App\Models\StaffTransaction;
use App\Models\User;
use App\Services\ReportService;
use App\Support\StaffTransactionCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StaffSalaryPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_salary_updates_funds_without_affecting_expenses_or_commission(): void
    {
        $this->travelTo(now()->setDate(2026, 8, 10));
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'salary@example.test', 'role' => 'admin', 'password' => 'password']));
        $commissionStaff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        $salaryStaff = Staff::create(['name' => 'Salary staff', 'commission_rate' => 0]);
        CashDeposit::create([
            'staff_id' => $commissionStaff->id, 'deposit_date' => '2026-08-01', 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);
        app(ReportService::class)->generateMonthlyCommission($commissionStaff, '2026-08-01');
        Livewire::test(CreateStaffTransaction::class)->fillForm([
            'staff_id' => $salaryStaff->id, 'transaction_date' => '2026-08-01', 'commission_month' => '2026-08-01',
            'type' => 'salary', 'paid_from' => 'cash', 'amount' => 100, 'description' => 'August salary',
        ])->call('create')->assertHasNoFormErrors();
        $salary = StaffTransaction::where('type', 'salary')->firstOrFail();
        $this->assertSame(0, Expense::count());
        $this->assertSame(900.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(0.0, BankTransaction::summary()['cash_expenses_pending_deduction']);
        $this->assertSame(100.0, BankTransaction::summary()['cash_staff_payments_pending_deduction']);
        $commission = MonthlyCommission::where('staff_id', $commissionStaff->id)->firstOrFail();
        $this->assertSame(250.0, (float) $commission->commission_amount);
        $this->assertSame(0.0, (float) $commission->advances_deducted);
        $this->assertSame(250.0, (float) app(ReportService::class)->generateMonthlyCommission($commissionStaff, '2026-08-01')->commission_amount);
        $this->assertFalse(MonthlyCommission::where('staff_id', $salaryStaff->id)->exists());
        $this->assertSame(0, StaffCommissionPayment::recordsQuery()->count());
        $report = app(ReportService::class)->daily('2026-08-01', withCapital: false);
        $this->assertSame(0.0, $report['expense_total']);
        $this->assertSame(1000.0, $report['net_cash_profit']);
        $yearly = app(ReportService::class)->yearly('2026');
        $this->assertSame(0.0, $yearly['expense_total']);
        $this->assertSame(1000.0, $yearly['net_profit']);

        Livewire::test(EditStaffTransaction::class, ['record' => $salary->id])
            ->fillForm(['paid_from' => 'bank', 'amount' => 125])->call('save')->assertHasNoFormErrors();
        $bank = BankTransaction::where('source_type', BankTransaction::SOURCE_STAFF_TRANSACTION)->where('source_id', $salary->id)->firstOrFail();
        $bankId = $bank->id;
        $this->assertSame(1000.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(-125.0, BankTransaction::summary()['account_balances']['rf_account']);
        $this->assertSame(250.0, (float) $commission->refresh()->commission_amount);
        Livewire::test(EditStaffTransaction::class, ['record' => $salary->id])
            ->fillForm(['paid_from' => 'saving_account', 'amount' => 140])->call('save')->assertHasNoFormErrors();
        $this->assertSame($bankId, $bank->refresh()->id);
        $this->assertSame('saving_account', $bank->bank_account);
        $this->assertSame(0.0, BankTransaction::summary()['account_balances']['rf_account']);
        $this->assertSame(-140.0, BankTransaction::summary()['account_balances']['saving_account']);
        $this->assertSame(1, BankTransaction::where('type', 'staff_payment')->count());
        $this->assertSame(0, BankTransaction::where('source_type', BankTransaction::SOURCE_EXPENSE)->count());

        Livewire::test(EditStaffTransaction::class, ['record' => $salary->id])
            ->fillForm(['paid_from' => 'cash', 'amount' => 160])->call('save')->assertHasNoFormErrors();
        $this->assertSame(160.0, (float) $salary->refresh()->amount);
        $this->assertSame('cash', $salary->paid_from);
        $this->assertSame(840.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(250.0, (float) $commission->refresh()->commission_amount);
        $this->assertSame(0, Expense::count());
        $salary->delete();
        $this->assertFalse(StaffTransaction::whereKey($salary->id)->exists());
        $this->assertSame(1000.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(250.0, (float) $commission->refresh()->commission_amount);
        $this->travelBack();
    }

    public function test_salary_date_changes_and_deletion_update_cash_as_of_payment_date(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $staff = Staff::create(['name' => 'Salary staff', 'commission_rate' => 0]);
        $commissionStaff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        CashDeposit::create([
            'staff_id' => $commissionStaff->id, 'deposit_date' => '2026-08-01', 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);
        app(ReportService::class)->generateMonthlyCommission($commissionStaff, '2026-08-01');
        $salary = StaffTransaction::create([
            'staff_id' => $staff->id, 'transaction_date' => '2026-08-01', 'type' => 'salary', 'paid_from' => 'cash', 'amount' => 100,
        ]);
        $this->assertSame(900.0, BankTransaction::summary('2026-08-31')['collection_cash_pending_deposit']);
        $this->assertSame(0.0, (float) app(ReportService::class)->daily('2026-08-01', withCapital: false)['expense_total']);
        $commission = MonthlyCommission::where('staff_id', $commissionStaff->id)->whereDate('month', '2026-08-01')->firstOrFail();
        $this->assertSame(250.0, (float) $commission->commission_amount);
        $salary->update(['transaction_date' => '2026-09-01']);
        $this->assertSame(250.0, (float) $commission->refresh()->commission_amount);
        $this->assertSame(0.0, (float) app(ReportService::class)->daily('2026-08-01', withCapital: false)['expense_total']);
        $this->assertSame(0.0, (float) app(ReportService::class)->daily('2026-09-01', withCapital: false)['expense_total']);
        $this->assertSame(1000.0, BankTransaction::summary('2026-08-31')['collection_cash_pending_deposit']);
        $this->assertSame(900.0, BankTransaction::summary('2026-09-30')['collection_cash_pending_deposit']);
        $salary->delete();
        $this->assertSame(0, Expense::count());
        $this->assertSame(0.0, (float) app(ReportService::class)->daily('2026-09-01', withCapital: false)['expense_total']);
        $this->assertSame(0.0, (float) BankTransaction::summary()['cash_in_bank']);
        $this->travelBack();
    }

    public function test_salary_cannot_be_split_using_commission_staff_option(): void
    {
        $this->expectException(ValidationException::class);
        StaffTransactionCreator::create(['type' => 'salary', 'amount' => 100, 'split_between_all_staff' => true]);
    }

    public function test_salary_upgrade_preserves_existing_records_and_bank_ids(): void
    {
        $staff = Staff::create(['name' => 'Legacy salary staff', 'commission_rate' => 0]);
        $commissionStaff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        CashDeposit::create([
            'staff_id' => $commissionStaff->id, 'deposit_date' => '2026-08-01', 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);
        app(ReportService::class)->generateMonthlyCommission($commissionStaff, '2026-08-01');
        $salary = StaffTransaction::create([
            'staff_id' => $staff->id, 'transaction_date' => '2026-08-01', 'type' => 'salary', 'paid_from' => 'bank', 'amount' => 100,
        ]);
        DB::table('expenses')->insert([
            'staff_transaction_id' => $salary->id, 'staff_id' => $staff->id,
            'expense_date' => '2026-08-01', 'category' => 'Salary', 'description' => 'Legacy salary', 'amount' => 100, 'paid_from' => 'bank',
        ]);
        $commission = MonthlyCommission::where('staff_id', $commissionStaff->id)->firstOrFail();
        $commission->update(['expense_total' => 100, 'net_profit' => 900, 'commission_amount' => 225, 'balance_due' => 225]);
        $later = app(ReportService::class)->generateMonthlyCommission($commissionStaff, '2026-09-01');
        $commissionStaff->update(['is_active' => false]);
        $bankBefore = (array) DB::table('bank_transactions')->first();
        $salaryBefore = (array) DB::table('staff_transactions')->find($salary->id);
        $migration = require database_path('migrations/2026_10_05_000003_exclude_owner_salaries_from_expenses.php');
        $migration->up();
        $this->assertSame($salaryBefore, (array) DB::table('staff_transactions')->find($salary->id));
        $this->assertSame($bankBefore, (array) DB::table('bank_transactions')->first());
        $this->assertSame(0, Expense::count());
        $this->assertSame(250.0, (float) $commission->refresh()->commission_amount);
        $this->assertSame(250.0, (float) $commission->balance_due);
        $this->assertSame(0.0, (float) $commission->expense_total);
        $this->assertSame(250.0, (float) $later->refresh()->carried_forward_from_previous);
        $this->assertSame(250.0, (float) $later->balance_due);
        $migration->up();
        $this->assertSame(250.0, (float) $commission->refresh()->balance_due);
        $this->assertSame(1, BankTransaction::where('type', 'staff_payment')->count());
    }
}
