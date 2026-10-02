<?php

namespace Tests\Feature;

use App\Filament\Resources\MonthlyClosings\Pages\CreateMonthlyClosing;
use App\Models\CashDeposit;
use App\Models\Staff;
use App\Models\User;
use App\Services\MonthlyClosingPreview;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class MonthlyClosingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_reuses_queries_but_recalculates_changed_payments_and_cleared_data(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10));
        $staff = Staff::create(['name' => 'Staff', 'commission_rate' => 25]);
        CashDeposit::create([
            'deposit_date' => '2026-08-01', 'staff_id' => $staff->id, 'closing_source' => 'manual',
            'manual_table_1_sale' => 1000, 'amount_collected_from_staff' => 1000,
        ]);
        $preview = app(MonthlyClosingPreview::class);
        $override = ['month' => '2026-08-01', 'rent_total' => 0, 'commission_paid_overrides' => [$staff->id => 50]];
        $first = $preview->report('2026-08', $override);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame($first, $preview->report('2026-08', $override));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();

        $override['commission_paid_overrides'][$staff->id] = 300;
        $updated = $preview->report('2026-08', $override);
        $this->assertSame(300.0, $updated['staff_shares'][0]['paid_amount']);
        $this->assertSame(-50.0, $updated['staff_shares'][0]['remaining_balance']);

        $preview->clear();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $preview->report('2026-08', $override);
        $this->assertNotEmpty(DB::getQueryLog());
        DB::disableQueryLog();
        $this->travelBack();
    }

    public function test_closing_form_shares_the_report_between_staff_helpers_and_printable_preview(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10));
        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'performance@example.test', 'role' => 'admin', 'password' => 'password',
        ]));
        foreach (['First staff', 'Second staff', 'Third staff'] as $name) {
            Staff::create(['name' => $name, 'commission_rate' => 25]);
        }
        $service = Mockery::mock(ReportService::class)->makePartial();
        $service->shouldReceive('monthly')->once()->passthru();
        $this->app->instance(ReportService::class, $service);

        Livewire::test(CreateMonthlyClosing::class)->assertSee('Monthly commission');
        $this->travelBack();
    }

    public function test_batch_commission_generation_builds_one_report_for_all_staff(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10));
        foreach (['First staff', 'Second staff', 'Third staff'] as $name) {
            Staff::create(['name' => $name, 'commission_rate' => 25]);
        }
        $service = Mockery::mock(ReportService::class)->makePartial();
        $service->shouldReceive('monthly')->once()->passthru();

        $this->assertCount(3, $service->generateMonthlyCommissions('2026-08'));
        $this->travelBack();
    }
}
