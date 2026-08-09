<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActiveGames;
use App\Filament\Widgets\PaymentDues;
use App\Filament\Widgets\SaleManagerOverview;
use App\Filament\Widgets\TableWiseSalesStats;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use UnitEnum;

class ClubOperationsDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/club-operations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Club Manager Operations';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Salesman Dashboard';

    protected static ?string $title = 'Salesman Dashboard';

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
        return [
            SaleManagerOverview::class,
            TableWiseSalesStats::class,
            ActiveGames::class,
            PaymentDues::class,
        ];
    }
}
