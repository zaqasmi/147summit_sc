<?php

namespace Tests\Feature;

use App\Filament\Resources\StaffTransactions\Pages\EditStaffTransaction;
use App\Filament\Resources\StaffTransactions\Pages\ListStaffTransactions;
use App\Models\BankTransaction;
use App\Models\CashDeposit;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\StaffCommissionPayment;
use App\Models\StaffTransaction;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StaffCommissionSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_payments_appear_in_staff_transactions_and_edits_and_deletes_update_both(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'sync@example.test', 'role' => 'admin', 'password' => 'password']));
        $staff = $this->staffWithSales('Staff');
        $service = app(ReportService::class);
        $commission = $service->generateMonthlyCommission($staff, '2026-08', paidAmount: 50, paidFrom: 'bank');
        $later = $service->generateMonthlyCommission($staff, '2026-09');
        $linked = $commission->staffPayment()->firstOrFail();
        $bank = BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->where('source_id', $commission->id)->firstOrFail();
        $bankId = $bank->id;
        $this->assertSame('closing_payment', $linked->type);
        $this->assertSame(50.0, (float) $linked->amount);
        $this->assertSame(200.0, (float) $commission->balance_due);
        $this->assertSame(0.0, (float) $commission->advances_deducted);
        Livewire::test(ListStaffTransactions::class)->assertCanSeeTableRecords([$linked]);

        Livewire::test(EditStaffTransaction::class, ['record' => $linked->id])
            ->fillForm(['amount' => 300, 'paid_from' => 'saving_account', 'transaction_date' => '2026-09-04', 'description' => 'Corrected closing payment'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(300.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame('2026-09-04', $commission->paid_on->toDateString());
        $this->assertSame(-50.0, (float) $commission->balance_due);
        $this->assertSame(-50.0, (float) $later->refresh()->balance_due);
        $this->assertSame($bankId, $bank->refresh()->id);
        $this->assertSame('saving_account', $bank->bank_account);
        $this->assertSame(1, BankTransaction::where('type', 'staff_payment')->count());
        $this->assertSame(1, StaffCommissionPayment::recordsQuery()->count());
        $this->assertSame(-300.0, BankTransaction::summary()['account_balances']['saving_account']);

        $service->generateMonthlyCommission($staff, '2026-08', paidAmount: 100, paidFrom: 'cash');
        $this->assertSame(100.0, (float) $linked->refresh()->amount);
        $this->assertSame('cash', $linked->paid_from);
        $this->assertSame(1, StaffTransaction::count());
        $this->assertSame(900.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(100.0, BankTransaction::summary()['cash_staff_payments_pending_deduction']);
        $this->assertSame(0, BankTransaction::where('type', 'staff_payment')->count());

        $linked->delete();
        $this->assertSame(0.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame(250.0, (float) $commission->balance_due);
        $this->assertSame(250.0, (float) $later->refresh()->balance_due);
        $this->assertSame(1000.0, BankTransaction::summary()['collection_cash_pending_deposit']);
        $this->assertSame(0, StaffTransaction::count());
        $this->travelBack();
    }

    public function test_normal_transactions_refresh_old_and_new_staff_months_when_moved_or_retyped(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $first = $this->staffWithSales('First staff');
        $second = Staff::create(['name' => 'Second staff', 'commission_rate' => 25]);
        $payment = StaffTransaction::create([
            'staff_id' => $first->id, 'transaction_date' => '2026-08-05', 'commission_month' => '2026-08-01',
            'type' => 'advance', 'paid_from' => 'cash', 'amount' => 40,
        ]);
        $august = MonthlyCommission::where('staff_id', $first->id)->whereDate('month', '2026-08-01')->firstOrFail();
        $this->assertSame(40.0, (float) $august->advances_deducted);
        $payment->update(['staff_id' => $second->id, 'commission_month' => '2026-09-01', 'amount' => 60]);
        $this->assertSame(0.0, (float) $august->refresh()->advances_deducted);
        $september = MonthlyCommission::where('staff_id', $second->id)->whereDate('month', '2026-09-01')->firstOrFail();
        $this->assertSame(60.0, (float) $september->advances_deducted);
        $this->assertSame(-60.0, (float) $september->balance_due);
        $payment->update(['type' => 'adjustment']);
        $this->assertSame(0.0, (float) $september->refresh()->advances_deducted);
        $this->assertSame(0.0, (float) $september->balance_due);
        $payment->update(['type' => 'payout']);
        $this->assertSame(60.0, (float) $september->refresh()->advances_deducted);
        $payment->delete();
        $this->assertSame(0.0, (float) $september->refresh()->advances_deducted);
        $this->travelBack();
    }

    public function test_upgrade_backfills_closing_payments_without_altering_existing_payment_or_bank_ids(): void
    {
        $migration = require database_path('migrations/2026_10_05_000001_link_closing_payments_to_staff_transactions.php');
        $migration->down();
        $staff = Staff::create(['name' => 'Legacy staff', 'commission_rate' => 25]);
        $commission = MonthlyCommission::withoutEvents(fn () => MonthlyCommission::create([
            'staff_id' => $staff->id, 'month' => '2026-08-01', 'commission_amount' => 250,
            'paid_amount' => 50, 'paid_from' => 'bank', 'paid_on' => '2026-09-01', 'balance_due' => 200,
        ]));
        $unknown = MonthlyCommission::withoutEvents(fn () => MonthlyCommission::create([
            'staff_id' => $staff->id, 'month' => '2026-07-01', 'commission_amount' => 100,
            'paid_amount' => 20, 'balance_due' => 80,
        ]));
        $legacyId = DB::table('staff_transactions')->insertGetId([
            'staff_id' => $staff->id, 'transaction_date' => '2026-08-02', 'commission_month' => '2026-08-01',
            'type' => 'advance', 'paid_from' => 'cash', 'amount' => 20,
        ]);
        BankTransaction::syncFromMonthlyCommission($commission);
        $bank = DB::table('bank_transactions')->first();
        $original = (array) DB::table('staff_transactions')->find($legacyId);
        $migration->up();
        $after = (array) DB::table('staff_transactions')->find($legacyId);
        unset($after['monthly_commission_id']);
        $this->assertSame($original, $after);
        $this->assertSame((array) $bank, (array) DB::table('bank_transactions')->first());
        $linked = $commission->staffPayment()->firstOrFail();
        $this->assertSame(50.0, (float) $linked->amount);
        $unknownPayment = $unknown->staffPayment()->firstOrFail();
        $this->assertSame('Not recorded', $unknownPayment->paid_from_label);
        $this->assertSame('2026-07-31', $unknownPayment->transaction_date->toDateString());
        $this->assertSame('closing_payment', $linked->type);
        $this->assertSame('2026-09-01', $linked->transaction_date->toDateString());
        $commission->save();
        $commission->save();
        $this->assertSame(1, $commission->staffPayment()->count());
        $this->assertSame($linked->id, $commission->staffPayment()->first()->id);
        $commission->update(['paid_amount' => 0]);
        $this->assertSame(0, $commission->staffPayment()->count());
        $this->assertTrue(StaffTransaction::whereKey($legacyId)->exists());
    }

    private function staffWithSales(string $name): Staff
    {
        $staff = Staff::create(['name' => $name, 'commission_rate' => 25]);
        CashDeposit::create([
            'staff_id' => $staff->id, 'deposit_date' => '2026-08-01', 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);

        return $staff;
    }
}
