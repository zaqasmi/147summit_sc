<?php

namespace App\Filament\Resources\CommissionRates;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\CommissionRates\Pages\CreateCommissionRate;
use App\Filament\Resources\CommissionRates\Pages\EditCommissionRate;
use App\Filament\Resources\CommissionRates\Pages\ListCommissionRates;
use App\Filament\Resources\CommissionRates\Pages\ViewCommissionRate;
use App\Filament\Resources\CommissionRates\Schemas\CommissionRateForm;
use App\Filament\Resources\CommissionRates\Schemas\CommissionRateInfolist;
use App\Filament\Resources\CommissionRates\Tables\CommissionRatesTable;
use App\Models\CommissionRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommissionRateResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = CommissionRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Commission Rates';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return CommissionRateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommissionRateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommissionRatesTable::configure($table);
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
            'index' => ListCommissionRates::route('/'),
            'create' => CreateCommissionRate::route('/create'),
            'view' => ViewCommissionRate::route('/{record}'),
            'edit' => EditCommissionRate::route('/{record}/edit'),
        ];
    }
}
