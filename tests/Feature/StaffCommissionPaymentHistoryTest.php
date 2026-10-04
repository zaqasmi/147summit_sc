<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Filament\Resources\MonthlyCommissions\Widgets\StaffCommissionPaymentHistory;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\StaffCommissionPayment;
use App\Models\StaffTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StaffCommissionPaymentHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_includes_both_payment_sources_once_and_matches_the_assigned_month_balance(): void
    {
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        $balance = $this->balance($staff, '2026-08-01', 200, 70, 50, 20, 100);
        $advance = $this->payment($staff, '2026-08-05', '2026-08-01', 'advance', 'cash', 40);
        $payout = $this->payment($staff, '2026-09-02', '2026-08-01', 'payout', 'bank', 30);
        $this->payment($staff, '2026-08-10', '2026-08-01', 'salary', 'cash', 80);
        $other = Staff::create(['name' => 'Salary staff', 'commission_rate' => 0]);
        $this->payment($other, '2026-08-10', '2026-08-01', 'advance', 'cash', 10);
        $rows = StaffCommissionPayment::recordsQuery()->orderBy('id')->get();

        $this->assertCount(3, $rows);
        $this->assertSame(120.0, (float) $rows->sum('amount'));
        $this->assertSame([-$balance->id, $advance->id, $payout->id], $rows->pluck('id')->all());
        foreach ($rows as $row) {
            $this->assertSame('2026-08', $row->commission_month);
            $this->assertSame(200.0, (float) $row->commission_amount);
            $this->assertSame(120.0, (float) $row->total_paid);
            $this->assertSame(100.0, (float) $row->balance_due);
            $this->assertSame($staff->name, $row->staff->name);
        }
        $this->assertSame('2026-09-02', $rows->last()->payment_date->toDateString());
        $this->assertSame('closing_payment', $rows->first()->type);
        $this->assertSame('saving_account', $rows->first()->paid_from);
        $this->assertSame(4, StaffTransaction::count());
        $this->assertSame(1, MonthlyCommission::count());
    }

    public function test_legacy_months_unrecorded_dates_and_ungenerated_balances_remain_visible(): void
    {
        $staff = Staff::create(['name' => 'Historical staff', 'commission_rate' => 0, 'is_active' => false]);
        $balance = $this->balance($staff, '2026-08-01', 100, 0, 125, 0, -25);
        MonthlyCommission::withoutEvents(fn () => $balance->update(['paid_on' => null]));
        $legacy = $this->payment($staff, '2026-08-17', '2026-08-01', 'advance', 'bank', 20);
        DB::table('staff_transactions')->where('id', $legacy->id)->update(['commission_month' => null]);
        $newStaff = Staff::create(['name' => 'New staff', 'commission_rate' => 25]);
        $ungenerated = $this->payment($newStaff, '2026-09-05', '2026-09-01', 'payout', 'cash', 15);
        $rows = StaffCommissionPayment::recordsQuery()->get()->keyBy('id');

        $this->assertCount(3, $rows);
        $this->assertSame('2026-08', $rows[$legacy->id]->commission_month);
        $this->assertSame(-25.0, (float) $rows[-$balance->id]->balance_due);
        $this->assertNull($rows[-$balance->id]->payment_date);
        $this->assertNull($rows[$ungenerated->id]->balance_due);
    }

    public function test_payment_table_filters_independently_by_staff_month_and_source(): void
    {
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'history@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        $other = Staff::create(['name' => 'Other staff', 'commission_rate' => 25]);
        $balance = $this->balance($staff, '2026-08-01', 100, 30, 80, 0, -10);
        $this->payment($staff, '2026-08-05', '2026-08-01', 'advance', 'cash', 30);
        $this->payment($other, '2026-09-02', '2026-09-01', 'payout', 'bank', 15);
        $rows = StaffCommissionPayment::recordsQuery()->get();
        $closing = $rows->firstWhere('id', -$balance->id);

        Livewire::test(ListMonthlyCommissions::class)->assertSeeLivewire(StaffCommissionPaymentHistory::class);
        $widget = Livewire::test(StaffCommissionPaymentHistory::class)
            ->assertCanSeeTableRecords($rows)
            ->assertSee('Advance carried forward')
            ->assertSee('Saving Account')
            ->filterTable('commission_month', '2026-08')
            ->assertCountTableRecords(2)
            ->filterTable('staff_id', $staff->id)
            ->filterTable('paid_from', 'saving_account')
            ->assertCanSeeTableRecords([$closing])
            ->assertCountTableRecords(1);
        $widget->dispatch('cash-bank-balances-updated')->assertCountTableRecords(1);
    }

    private function balance(Staff $staff, string $month, float $commission, float $ledgerPaid, float $closingPaid, float $previous, float $remaining): MonthlyCommission
    {
        return MonthlyCommission::withoutEvents(fn () => MonthlyCommission::create([
            'staff_id' => $staff->id, 'month' => $month, 'commission_amount' => $commission,
            'advances_deducted' => $ledgerPaid, 'paid_amount' => $closingPaid,
            'carried_forward_from_previous' => $previous, 'balance_due' => $remaining,
            'paid_from' => 'saving_account', 'paid_on' => '2026-09-01',
        ]));
    }

    private function payment(Staff $staff, string $date, string $month, string $type, string $source, float $amount): StaffTransaction
    {
        return StaffTransaction::withoutEvents(fn () => StaffTransaction::create([
            'staff_id' => $staff->id, 'transaction_date' => $date, 'commission_month' => $month,
            'type' => $type, 'paid_from' => $source, 'amount' => $amount,
        ]));
    }
}
