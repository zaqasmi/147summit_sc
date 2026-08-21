<?php

namespace App\Filament\Pages;

use App\Models\MonthlyClosing;
use App\Services\ReportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class MonthlyReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Monthly Commission Report';

    protected static ?string $title = 'Monthly Commission Report';

    protected string $view = 'filament.pages.monthly-report';

    public ?string $month = null;

    public float|int|string|null $rentTotal = null;

    public float|int|string|null $rentPaidAmount = null;

    public ?string $rentPaidFrom = 'bank';

    public float|int|string|null $constructionDeductionAmount = null;

    public float|int|string|null $constructionReceivedAmount = null;

    public ?string $constructionAccountName = null;

    public bool $liabilitiesVerified = false;

    public ?string $closingNotes = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->canViewCommissionReports() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->month ??= today()->format('Y-m');
        $this->loadMonthlyClosingForm();
    }

    public function updatedMonth(): void
    {
        $this->loadMonthlyClosingForm();
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return app(ReportService::class)->monthly($this->month ?? today(), $this->monthlyClosingPreview());
    }

    public function saveMonthlyClosingDraft(): void
    {
        if (! $this->canManageMonthlyClosing()) {
            Notification::make()
                ->title('Only admin can save monthly closing')
                ->danger()
                ->send();

            return;
        }

        $this->persistMonthlyClosing(MonthlyClosing::STATUS_DRAFT);

        Notification::make()
            ->title('Monthly closing draft saved')
            ->success()
            ->send();
    }

    public function closeMonth(): void
    {
        if (! $this->canManageMonthlyClosing()) {
            Notification::make()
                ->title('Only admin can close the month')
                ->danger()
                ->send();

            return;
        }

        $input = $this->monthlyClosingPreview();

        if (abs(((float) $input['rent_paid_amount'] + (float) $input['construction_deduction_amount']) - (float) $input['rent_total']) > 0.01) {
            Notification::make()
                ->title('Rent split does not match total rent')
                ->body('Rent paid plus construction deduction must equal total rent before closing the month.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->liabilitiesVerified) {
            Notification::make()
                ->title('Verify liabilities first')
                ->body('Tick liabilities paid and verified before closing the month.')
                ->danger()
                ->send();

            return;
        }

        $this->persistMonthlyClosing(MonthlyClosing::STATUS_CLOSED);
        $this->loadMonthlyClosingForm();

        Notification::make()
            ->title('Month closed')
            ->body('Rent, construction deduction, commission, and liabilities have been saved for this month.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyClosingPreview(): array
    {
        $month = Carbon::parse($this->month ?? today())->startOfMonth();
        $closing = MonthlyClosing::forMonth($month);
        $defaults = MonthlyClosing::defaultsForMonth($month);

        if ($this->canManageMonthlyClosing()) {
            $rentTotal = $this->moneyNumber($this->rentTotal);
            $rentPaid = $this->moneyNumber($this->rentPaidAmount);
            $rentPaidFrom = $this->rentPaidFrom ?: 'bank';
            $constructionDeduction = $this->moneyNumber($this->constructionDeductionAmount);
            $constructionReceived = $this->moneyNumber($this->constructionReceivedAmount);
            $constructionAccount = $this->constructionAccountName ?: 'Construction deduction account';
            $liabilitiesVerified = $this->liabilitiesVerified;
            $notes = $this->closingNotes;
        } else {
            $rentTotal = $this->moneyNumber($closing?->rent_total ?? $defaults['rent_total']);
            $rentPaid = $this->moneyNumber($closing?->rent_paid_amount ?? $defaults['rent_paid_amount']);
            $rentPaidFrom = $closing?->rent_paid_from ?? $defaults['rent_paid_from'];
            $constructionDeduction = $this->moneyNumber($closing?->construction_deduction_amount ?? $defaults['construction_deduction_amount']);
            $constructionReceived = $this->moneyNumber($closing?->construction_received_amount ?? $defaults['construction_received_amount']);
            $constructionAccount = $closing?->construction_account_name ?? $defaults['construction_account_name'];
            $liabilitiesVerified = (bool) ($closing?->liabilities_verified ?? $defaults['liabilities_verified']);
            $notes = $closing?->notes;
        }

        return [
            'month' => $month->toDateString(),
            'rent_total' => $rentTotal,
            'rent_paid_amount' => $rentPaid,
            'rent_paid_from' => $rentPaidFrom,
            'construction_deduction_amount' => $constructionDeduction,
            'construction_received_amount' => $constructionReceived,
            'construction_account_name' => $constructionAccount,
            'construction_balance' => round(max(0, $constructionDeduction - $constructionReceived), 2),
            'liabilities_verified' => $liabilitiesVerified,
            'notes' => $notes,
            'status' => $closing?->status ?? MonthlyClosing::STATUS_DRAFT,
        ];
    }

    public function isMonthClosed(): bool
    {
        return MonthlyClosing::forMonth($this->month ?? today())?->status === MonthlyClosing::STATUS_CLOSED;
    }

    public function canManageMonthlyClosing(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function canViewOwnerProfit(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }

    public function percent(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 2).'%';
    }

    private function loadMonthlyClosingForm(): void
    {
        $month = Carbon::parse($this->month ?? today())->startOfMonth();
        $closing = MonthlyClosing::forMonth($month);
        $defaults = MonthlyClosing::defaultsForMonth($month);

        $this->rentTotal = (float) ($closing?->rent_total ?? $defaults['rent_total']);
        $this->rentPaidAmount = (float) ($closing?->rent_paid_amount ?? $defaults['rent_paid_amount']);
        $this->rentPaidFrom = $closing?->rent_paid_from ?? $defaults['rent_paid_from'];
        $this->constructionDeductionAmount = (float) ($closing?->construction_deduction_amount ?? $defaults['construction_deduction_amount']);
        $this->constructionReceivedAmount = (float) ($closing?->construction_received_amount ?? $defaults['construction_received_amount']);
        $this->constructionAccountName = $closing?->construction_account_name ?? $defaults['construction_account_name'];
        $this->liabilitiesVerified = (bool) ($closing?->liabilities_verified ?? $defaults['liabilities_verified']);
        $this->closingNotes = $closing?->notes;
    }

    private function persistMonthlyClosing(string $status): MonthlyClosing
    {
        $month = Carbon::parse($this->month ?? today())->startOfMonth();
        $preview = $this->monthlyClosingPreview();
        $report = app(ReportService::class)->monthly($month, $preview);
        $closing = MonthlyClosing::query()->updateOrCreate(
            ['month' => $month->toDateString()],
            [
                'status' => $status,
                'rent_total' => $preview['rent_total'],
                'rent_paid_amount' => $preview['rent_paid_amount'],
                'rent_paid_from' => $preview['rent_paid_from'],
                'construction_deduction_amount' => $preview['construction_deduction_amount'],
                'construction_received_amount' => $preview['construction_received_amount'],
                'construction_account_name' => $preview['construction_account_name'],
                'sales_total' => $report['sales_total'],
                'cash_collected' => $report['cash_collected'],
                'expense_total' => $report['expense_total'],
                'net_profit' => $report['net_profit'],
                'commission_amount' => $report['commission_estimate'],
                'staff_paid_total' => $report['staff_paid_total'],
                'liabilities_paid_amount' => $report['capital_installments_paid'],
                'liabilities_verified' => $preview['liabilities_verified'],
                'closed_at' => $status === MonthlyClosing::STATUS_CLOSED ? now() : null,
                'closed_by' => $status === MonthlyClosing::STATUS_CLOSED ? auth()->id() : null,
                'notes' => $preview['notes'],
            ],
        );

        return $closing;
    }

    private function moneyNumber(float|int|string|null $amount): float
    {
        return round((float) ($amount ?? 0), 2);
    }
}
