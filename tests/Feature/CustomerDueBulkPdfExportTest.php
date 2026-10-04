<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerDues\Pages\ListCustomerDues;
use App\Models\CustomerDue;
use App\Models\User;
use App\Services\CustomerDuePdfReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerDueBulkPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public static function exportRoles(): array
    {
        return [['admin'], ['sale_manager']];
    }

    #[DataProvider('exportRoles')]
    public function test_bulk_action_downloads_only_selected_dues_in_balance_order(string $role): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5));
        $this->actingAs(User::create([
            'name' => 'Export user', 'email' => $role.'@example.test', 'role' => $role, 'password' => 'password',
        ]));
        $low = CustomerDue::create(['customer_name' => 'Alpha small due', 'opening_balance' => 90]);
        $high = CustomerDue::create(['customer_name' => 'Zulu large due', 'opening_balance' => 1000]);
        CustomerDue::create(['customer_name' => 'Unselected customer', 'opening_balance' => 5000]);
        $records = collect([$low, $high]);
        $expected = app(CustomerDuePdfReport::class)->generate($records);

        Livewire::test(ListCustomerDues::class)
            ->assertTableBulkActionVisible('exportPdf')
            ->callTableBulkAction('exportPdf', $records)
            ->assertFileDownloaded('customer-dues-selected-2026-10-05.pdf', $expected, 'application/pdf');

        $this->assertLessThan(strpos($expected, '(Alpha small due)'), strpos($expected, '(Zulu large due)'));
        $this->assertStringNotContainsString('Unselected customer', $expected);
        $this->assertStringContainsString('Rs 1,090.00', $expected);
        $this->assertSame(3, CustomerDue::count());
        $this->travelBack();
    }

    public function test_full_export_keeps_descending_balance_order_across_pdf_pages(): void
    {
        for ($amount = 1; $amount <= 25; $amount++) {
            CustomerDue::create(['customer_name' => 'Customer '.sprintf('%02d', $amount), 'opening_balance' => $amount]);
        }

        $pdf = app(CustomerDuePdfReport::class)->generate();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('/Count 2', $pdf);
        $previousPosition = -1;
        for ($amount = 25; $amount >= 1; $amount--) {
            $position = strpos($pdf, '(Customer '.sprintf('%02d', $amount).')');
            $this->assertNotFalse($position);
            $this->assertGreaterThan($previousPosition, $position);
            $previousPosition = $position;
        }
        $this->assertStringContainsString('Rs 325.00', $pdf);
    }

    public function test_user_without_customer_dues_access_cannot_export(): void
    {
        $this->actingAs(User::create([
            'name' => 'Other user', 'email' => 'other@example.test', 'role' => 'viewer', 'password' => 'password',
        ]));

        Livewire::test(ListCustomerDues::class)->assertForbidden();
        $this->get(route('customer-dues.export-pdf'))->assertForbidden();
    }
}
