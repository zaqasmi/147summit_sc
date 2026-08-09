<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DailyReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Daily Report';

    protected string $view = 'filament.pages.daily-report';

    public ?string $date = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->date ??= today()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return app(ReportService::class)->daily($this->date ?? today());
    }

    public function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }
}
