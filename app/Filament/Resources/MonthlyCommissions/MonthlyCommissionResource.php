<?php

namespace App\Filament\Resources\MonthlyCommissions;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\MonthlyCommissions\Pages\ListMonthlyCommissions;
use App\Filament\Resources\MonthlyCommissions\Pages\ViewMonthlyCommission;
use App\Filament\Resources\MonthlyCommissions\Schemas\MonthlyCommissionForm;
use App\Filament\Resources\MonthlyCommissions\Schemas\MonthlyCommissionInfolist;
use App\Filament\Resources\MonthlyCommissions\Tables\MonthlyCommissionsTable;
use App\Models\MonthlyCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MonthlyCommissionResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = MonthlyCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return MonthlyCommissionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MonthlyCommissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonthlyCommissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlyCommissions::route('/'),
            'view' => ViewMonthlyCommission::route('/{record}'),
        ];
    }
}
