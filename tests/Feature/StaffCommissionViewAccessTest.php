<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Filament\Resources\MonthlyCommissions\Widgets\StaffCommissionOverallSummary;
use App\Filament\Resources\MonthlyCommissions\Widgets\StaffCommissionPaymentHistory;
use App\Filament\Widgets\CashToDepositSummary;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffCommissionViewAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function viewRoles(): array
    {
        return [['sale_manager'], ['viewer']];
    }

    #[DataProvider('viewRoles')]
    public function test_non_admin_users_can_view_all_commission_information_without_changing_records(string $role): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $staff = Staff::create(['name' => 'Commission staff', 'commission_rate' => 25]);
        $record = MonthlyCommission::create([
            'staff_id' => $staff->id, 'month' => '2026-08-01', 'commission_amount' => 100,
            'paid_amount' => 25, 'paid_from' => 'bank', 'balance_due' => 75,
        ]);
        $before = $this->financialRecords();

        $this->actingAs(User::create([
            'name' => 'View user', 'email' => $role.'@example.test', 'role' => $role, 'password' => 'password',
        ]));
        $this->assertTrue(MonthlyCommissionResource::shouldRegisterNavigation());
        $this->assertTrue(MonthlyCommissionResource::canView($record));
        $this->assertFalse(MonthlyCommissionResource::canCreate());
        $this->assertFalse(MonthlyCommissionResource::canEdit($record));
        $this->assertFalse(MonthlyCommissionResource::canDelete($record));
        $this->assertFalse(MonthlyCommissionResource::canDeleteAny());
        $this->get(MonthlyCommissionResource::getUrl('index'))->assertOk()
            ->assertSee('Staff-wise remaining commission')->assertSee('Commission staff payment history')
            ->assertDontSee('Cash to be deposited in bank')->assertDontSee('Balance as of today');
        $this->get(MonthlyCommissionResource::getUrl('view', ['record' => $record]))->assertOk()
            ->assertSee('Commission staff')
            ->assertDontSee('href="'.MonthlyCommissionResource::getUrl('edit', ['record' => $record]).'"', false);
        $this->get(MonthlyCommissionResource::getUrl('edit', ['record' => $record]))->assertForbidden();
        $page = Livewire::test(ListMonthlyCommissions::class)
            ->assertCanSeeTableRecords([$record])
            ->assertDontSeeLivewire(CashToDepositSummary::class)
            ->assertSeeLivewire(StaffCommissionOverallSummary::class)
            ->assertSeeLivewire(StaffCommissionPaymentHistory::class)
            ->assertActionHidden('generateMonth')
            ->assertActionHidden('recordCommissionPayout')
            ->assertTableActionHidden('recordPayout', $record)
            ->assertTableActionHidden('edit', $record)
            ->assertTableActionVisible('view', $record);
        foreach (['generateMonth', 'recordCommissionPayout'] as $action) {
            $page->call('mountAction', $action)->assertSet('mountedActions', [])
                ->call('callMountedAction');
        }
        $page->call('mountAction', 'recordPayout', [], ['table' => true, 'recordKey' => (string) $record->id])
            ->assertSet('mountedActions', [])->call('callMountedAction');
        $this->assertFalse(CashToDepositSummary::canView());
        Livewire::withoutLazyLoading()->test(CashToDepositSummary::class)->assertForbidden();
        $this->assertSame($before, $this->financialRecords());
        $this->travelBack();
    }

    public function test_guests_cannot_view_commission_balances(): void
    {
        $this->assertFalse(MonthlyCommissionResource::canAccess());
        $this->assertFalse(StaffCommissionPaymentHistory::canView());
        $this->assertFalse(StaffCommissionOverallSummary::canView());
        $this->get(MonthlyCommissionResource::getUrl('index'))->assertRedirect(route('filament.admin.auth.login'));
    }

    private function financialRecords(): array
    {
        return collect(['monthly_commissions', 'staff_transactions', 'bank_transactions'])
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all()])
            ->all();
    }
}
