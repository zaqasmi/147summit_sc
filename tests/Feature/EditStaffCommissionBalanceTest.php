<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Filament\Resources\MonthlyCommissions\Pages\EditMonthlyCommission;
use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Models\BankTransaction;
use App\Models\CashDeposit;
use App\Models\Staff;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditStaffCommissionBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_payments_recalculates_balances_and_preserves_bank_transaction_id(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'commission-edit@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        CashDeposit::create([
            'staff_id' => $staff->id, 'deposit_date' => '2026-08-01', 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);
        $service = app(ReportService::class);
        $commission = $service->generateMonthlyCommission($staff, '2026-08', paidAmount: 50, paidFrom: 'bank');
        $later = $service->generateMonthlyCommission($staff, '2026-09');
        $bankEntry = BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)
            ->where('source_id', $commission->id)->firstOrFail();
        $bankId = $bankEntry->id;
        Livewire::test(ListMonthlyCommissions::class)->assertTableActionExists('edit', record: $commission);
        $this->get(MonthlyCommissionResource::getUrl('view', ['record' => $commission]))
            ->assertOk()->assertSee(MonthlyCommissionResource::getUrl('edit', ['record' => $commission]), false);

        Livewire::test(EditMonthlyCommission::class, ['record' => $commission->id])
            ->fillForm(['paid_amount' => 300, 'paid_from' => 'saving_account', 'paid_on' => '2026-09-02', 'notes' => 'Corrected payment'])
            ->call('save')->assertHasNoFormErrors()
            ->assertFormSet(['balance_due' => '-50.00']);
        $commission->refresh();
        $this->assertSame(250.0, (float) $commission->commission_amount);
        $this->assertSame(-50.0, (float) $commission->balance_due);
        $this->assertSame('2026-09-02', $commission->paid_on->toDateString());
        $this->assertSame('Corrected payment', $commission->notes);
        $this->assertSame(-50.0, (float) $later->refresh()->carried_forward_from_previous);
        $this->assertSame(-50.0, (float) $later->balance_due);
        $this->assertSame($bankId, $bankEntry->refresh()->id);
        $this->assertSame('saving_account', $bankEntry->bank_account);
        $this->assertSame(300.0, (float) $bankEntry->amount);
        $this->assertSame(1, BankTransaction::where('source_type', BankTransaction::SOURCE_MONTHLY_COMMISSION)->where('source_id', $commission->id)->count());

        Livewire::test(EditMonthlyCommission::class, ['record' => $commission->id])
            ->fillForm(['paid_amount' => 75, 'paid_from' => 'cash'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(175.0, (float) $commission->refresh()->balance_due);
        $this->assertSame(175.0, (float) $later->refresh()->balance_due);
        $this->assertFalse(BankTransaction::where('id', $bankId)->exists());
        $this->travelBack();
    }

    public function test_invalid_payments_are_rejected_and_non_admins_cannot_edit(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'edit-validation@example.test', 'role' => 'admin', 'password' => 'password']);
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        $record = app(ReportService::class)->generateMonthlyCommission($staff, today(), paidAmount: 0, paidFrom: 'cash');
        $this->actingAs($admin);
        Livewire::test(EditMonthlyCommission::class, ['record' => $record->id])
            ->fillForm(['paid_amount' => -1])->call('save')->assertHasFormErrors(['paid_amount' => 'min']);
        Livewire::test(EditMonthlyCommission::class, ['record' => $record->id])
            ->fillForm(['paid_from' => 'invalid'])->call('save')->assertHasFormErrors(['paid_from' => 'in']);
        $this->assertSame(0.0, (float) $record->refresh()->paid_amount);
        $this->actingAs(User::create(['name' => 'Manager', 'email' => 'no-edit@example.test', 'role' => 'sale_manager', 'password' => 'password']));
        $this->get(MonthlyCommissionResource::getUrl('edit', ['record' => $record]))->assertForbidden();
    }
}
