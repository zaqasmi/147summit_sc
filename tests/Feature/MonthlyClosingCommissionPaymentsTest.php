<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyClosings\Pages\CreateMonthlyClosing;
use App\Filament\Resources\MonthlyClosings\Pages\EditMonthlyClosing;
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
        $later = app(ReportService::class)->generateMonthlyCommission($staff, '2026-09-01');
        $this->assertSame(75.0, (float) $later->carried_forward_from_previous);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->assertFormSet(['commission_paid_overrides' => [$staff->id => 75]])
            ->fillForm(['commission_paid_overrides' => [$staff->id => 250]])
            ->assertSee('Advance carried forward Rs 100.00')
            ->call('save')->assertHasNoFormErrors()
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(250.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame(-100.0, (float) $commission->balance_due);
        $this->assertSame(-100.0, (float) $later->refresh()->carried_forward_from_previous);
        $this->assertSame(-100.0, (float) $later->balance_due);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->fillForm(['commission_paid_overrides' => [$staff->id => -1]])
            ->call('save')->assertHasFormErrors(['commission_paid_overrides.'.$staff->id => 'min']);
        $this->assertSame(250.0, (float) $commission->refresh()->paid_amount);

        Livewire::test(EditMonthlyClosing::class, ['record' => $closing->id])
            ->callAction('commissionPayments', ['staff_'.$staff->id => 25])
            ->assertHasNoActionErrors()
            ->assertFormSet(['commission_paid_overrides' => [$staff->id => 25]])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(25.0, (float) $commission->refresh()->paid_amount);
        $this->assertSame(125.0, (float) $commission->balance_due);
        $this->assertSame(125.0, (float) $later->refresh()->balance_due);

        Livewire::test(CreateMonthlyClosing::class)
            ->set('data.month', '2026-08-01')
            ->assertSet('data.commission_paid_overrides.'.$staff->id, 25.0)
            ->set('data.month', '2026-10-01')
            ->assertSet('data.commission_paid_overrides.'.$staff->id, 0.0);
        $this->travelBack();
    }
}
