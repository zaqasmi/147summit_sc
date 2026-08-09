<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CompactFinancialOverview;
use App\Filament\Widgets\SaleManagerOverview;
use App\Filament\Widgets\TableWiseSalesStats;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'md' => 2,
                'xl' => 4,
            ])
            ->components([
                DatePicker::make('date')
                    ->label('Stats date')
                    ->default(today())
                    ->native(false)
                    ->live(),
            ]);
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        if (auth()->user()?->isAdmin()) {
            return [
                CompactFinancialOverview::class,
            ];
        }

        return [
            SaleManagerOverview::class,
            TableWiseSalesStats::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
