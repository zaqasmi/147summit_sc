<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class YearlyReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Yearly Report';

    protected string $view = 'filament.pages.yearly-report';

    public ?string $year = null;

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
        $this->year ??= today()->format('Y');
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return app(ReportService::class)->yearly(($this->year ?? today()->format('Y')).'-01-01');
    }

    public function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
