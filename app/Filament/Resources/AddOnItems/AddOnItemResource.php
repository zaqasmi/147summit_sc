<?php

namespace App\Filament\Resources\AddOnItems;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\AddOnItems\Pages\CreateAddOnItem;
use App\Filament\Resources\AddOnItems\Pages\EditAddOnItem;
use App\Filament\Resources\AddOnItems\Pages\ListAddOnItems;
use App\Filament\Resources\AddOnItems\Pages\ViewAddOnItem;
use App\Filament\Resources\AddOnItems\Schemas\AddOnItemForm;
use App\Filament\Resources\AddOnItems\Schemas\AddOnItemInfolist;
use App\Filament\Resources\AddOnItems\Tables\AddOnItemsTable;
use App\Models\AddOnItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AddOnItemResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = AddOnItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $navigationLabel = 'Add-on Items';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return AddOnItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AddOnItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AddOnItemsTable::configure($table);
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
            'index' => ListAddOnItems::route('/'),
            'create' => CreateAddOnItem::route('/create'),
            'view' => ViewAddOnItem::route('/{record}'),
            'edit' => EditAddOnItem::route('/{record}/edit'),
        ];
    }
}
