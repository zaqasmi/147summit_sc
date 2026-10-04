<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyCommissions\Widgets\StaffCommissionOverallSummary;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffCommissionStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_count_payments_once_and_separate_latest_staff_dues_from_advance_credit(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'commission-stats@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        $staff = Staff::create(['name' => 'Staff with dues', 'commission_rate' => 25, 'is_active' => false]);
        $overpaid = Staff::create(['name' => 'Overpaid staff', 'commission_rate' => 25, 'is_active' => false]);
        $this->balance($staff, '2026-07-01', 1000, 100, 100, 0, 800);
        $this->balance($staff, '2026-08-01', 200, 100, 750, 800, 150);
        $this->balance($staff, '2026-09-01', 100, 50, 100, 150, 100);
        $this->balance($overpaid, '2026-09-01', 100, 50, 90, 0, -40);

        Livewire::test(StaffCommissionOverallSummary::class)->assertSeeInOrder([
            'Commission earned', 'Rs 1,400.00',
            'Total commission paid to staff', 'Rs 1,340.00',
            'Total remaining to pay staff', 'Rs 100.00',
            'Advance credit carried forward', 'Rs 40.00',
            'Staff-wise remaining commission',
            'Overpaid staff — advance credit', 'Rs 40.00', 'Sep 2026', 'Paid Rs 140.00 in selected months',
            'Staff with dues — remaining due', 'Rs 100.00', 'Paid Rs 1,200.00 in selected months',
        ]);
        Livewire::test(StaffCommissionOverallSummary::class, [
            'tableFilters' => ['month_filter' => ['value' => '9'], 'year' => ['value' => '2026']],
        ])->assertSeeInOrder([
            'Commission earned', 'Rs 200.00',
            'Total commission paid to staff', 'Rs 290.00',
            'Total remaining to pay staff', 'Rs 100.00',
            'Advance credit carried forward', 'Rs 40.00',
            'Staff-wise remaining commission',
            'Overpaid staff — advance credit', 'Rs 40.00',
            'Staff with dues — remaining due', 'Rs 100.00', 'Paid Rs 150.00 in selected months',
        ]);
        Livewire::test(StaffCommissionOverallSummary::class, [
            'tableFilters' => ['staff_id' => ['value' => $staff->id], 'month_filter' => ['value' => '9']],
        ])->assertSeeInOrder([
            'Total commission paid to staff', 'Rs 150.00',
            'Total remaining to pay staff', 'Rs 100.00',
            'Advance credit carried forward', 'Rs 0.00',
            'Staff-wise remaining commission', 'Staff with dues — remaining due', 'Rs 100.00',
        ])->assertDontSee('Overpaid staff — advance credit');
        Livewire::test(StaffCommissionOverallSummary::class, [
            'tableFilters' => ['month_filter' => ['value' => '7']],
        ])->assertSeeInOrder(['Staff-wise remaining commission', 'Staff with dues — remaining due', 'Rs 800.00', 'Jul 2026'])
            ->assertDontSee('Overpaid staff — advance credit');
        $this->travelBack();
    }

    private function balance(Staff $staff, string $month, float $earned, float $ledgerPaid, float $closingPaid, float $previous, float $remaining): void
    {
        MonthlyCommission::withoutEvents(fn () => MonthlyCommission::create([
            'staff_id' => $staff->id, 'month' => $month, 'commission_amount' => $earned,
            'advances_deducted' => $ledgerPaid, 'paid_amount' => $closingPaid,
            'carried_forward_from_previous' => $previous, 'balance_due' => $remaining,
        ]));
    }
}
