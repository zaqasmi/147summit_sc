<?php

namespace App\Filament\Resources\SnookerTables;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\SnookerTables\Pages\CreateSnookerTable;
use App\Filament\Resources\SnookerTables\Pages\EditSnookerTable;
use App\Filament\Resources\SnookerTables\Pages\ListSnookerTables;
use App\Filament\Resources\SnookerTables\Pages\ViewSnookerTable;
use App\Filament\Resources\SnookerTables\Schemas\SnookerTableForm;
use App\Filament\Resources\SnookerTables\Schemas\SnookerTableInfolist;
use App\Filament\Resources\SnookerTables\Tables\SnookerTablesTable;
use App\Models\SnookerTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SnookerTableResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = SnookerTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return SnookerTableForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SnookerTableInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SnookerTablesTable::configure($table);
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
            'index' => ListSnookerTables::route('/'),
            'create' => CreateSnookerTable::route('/create'),
            'view' => ViewSnookerTable::route('/{record}'),
            'edit' => EditSnookerTable::route('/{record}/edit'),
        ];
    }
}
