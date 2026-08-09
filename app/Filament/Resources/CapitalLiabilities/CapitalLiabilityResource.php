<?php

namespace App\Filament\Resources\CapitalLiabilities;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\CapitalLiabilities\Pages\CreateCapitalLiability;
use App\Filament\Resources\CapitalLiabilities\Pages\EditCapitalLiability;
use App\Filament\Resources\CapitalLiabilities\Pages\ListCapitalLiabilities;
use App\Filament\Resources\CapitalLiabilities\Pages\ViewCapitalLiability;
use App\Filament\Resources\CapitalLiabilities\Schemas\CapitalLiabilityForm;
use App\Filament\Resources\CapitalLiabilities\Schemas\CapitalLiabilityInfolist;
use App\Filament\Resources\CapitalLiabilities\Tables\CapitalLiabilitiesTable;
use App\Models\CapitalLiability;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CapitalLiabilityResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = CapitalLiability::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Capital Liabilities';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return CapitalLiabilityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CapitalLiabilityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CapitalLiabilitiesTable::configure($table);
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
            'index' => ListCapitalLiabilities::route('/'),
            'create' => CreateCapitalLiability::route('/create'),
            'view' => ViewCapitalLiability::route('/{record}'),
            'edit' => EditCapitalLiability::route('/{record}/edit'),
        ];
    }
}
