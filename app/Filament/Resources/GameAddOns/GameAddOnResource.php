<?php

namespace App\Filament\Resources\GameAddOns;

use App\Filament\Concerns\AdminOnlyMutations;
use App\Filament\Resources\GameAddOns\Pages\CreateGameAddOn;
use App\Filament\Resources\GameAddOns\Pages\EditGameAddOn;
use App\Filament\Resources\GameAddOns\Pages\ListGameAddOns;
use App\Filament\Resources\GameAddOns\Pages\ViewGameAddOn;
use App\Filament\Resources\GameAddOns\Schemas\GameAddOnForm;
use App\Filament\Resources\GameAddOns\Schemas\GameAddOnInfolist;
use App\Filament\Resources\GameAddOns\Tables\GameAddOnsTable;
use App\Models\GameAddOn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameAddOnResource extends Resource
{
    use AdminOnlyMutations;

    protected static ?string $model = GameAddOn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $navigationLabel = 'Add-on Records';

    protected static string|\UnitEnum|null $navigationGroup = 'Club Manager Operations';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return GameAddOnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameAddOnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameAddOnsTable::configure($table);
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
            'index' => ListGameAddOns::route('/'),
            'create' => CreateGameAddOn::route('/create'),
            'view' => ViewGameAddOn::route('/{record}'),
            'edit' => EditGameAddOn::route('/{record}/edit'),
        ];
    }
}
