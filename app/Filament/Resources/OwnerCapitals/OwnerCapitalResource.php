<?php

namespace App\Filament\Resources\OwnerCapitals;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\OwnerCapitals\Pages\CreateOwnerCapital;
use App\Filament\Resources\OwnerCapitals\Pages\EditOwnerCapital;
use App\Filament\Resources\OwnerCapitals\Pages\ListOwnerCapitals;
use App\Filament\Resources\OwnerCapitals\Pages\ViewOwnerCapital;
use App\Filament\Resources\OwnerCapitals\Schemas\OwnerCapitalForm;
use App\Filament\Resources\OwnerCapitals\Schemas\OwnerCapitalInfolist;
use App\Filament\Resources\OwnerCapitals\Tables\OwnerCapitalsTable;
use App\Models\OwnerCapital;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OwnerCapitalResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = OwnerCapital::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyRupee;

    protected static ?string $navigationLabel = 'Owner Capital';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return OwnerCapitalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OwnerCapitalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OwnerCapitalsTable::configure($table);
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
            'index' => ListOwnerCapitals::route('/'),
            'create' => CreateOwnerCapital::route('/create'),
            'view' => ViewOwnerCapital::route('/{record}'),
            'edit' => EditOwnerCapital::route('/{record}/edit'),
        ];
    }
}
