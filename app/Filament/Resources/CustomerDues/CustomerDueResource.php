<?php

namespace App\Filament\Resources\CustomerDues;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\CustomerDues\Pages\CreateCustomerDue;
use App\Filament\Resources\CustomerDues\Pages\EditCustomerDue;
use App\Filament\Resources\CustomerDues\Pages\ListCustomerDues;
use App\Filament\Resources\CustomerDues\Pages\ViewCustomerDue;
use App\Filament\Resources\CustomerDues\Schemas\CustomerDueForm;
use App\Filament\Resources\CustomerDues\Schemas\CustomerDueInfolist;
use App\Filament\Resources\CustomerDues\Tables\CustomerDuesTable;
use App\Models\CustomerDue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerDueResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = CustomerDue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Customer Dues';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CustomerDueForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerDueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerDuesTable::configure($table);
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
            'index' => ListCustomerDues::route('/'),
            'create' => CreateCustomerDue::route('/create'),
            'view' => ViewCustomerDue::route('/{record}'),
            'edit' => EditCustomerDue::route('/{record}/edit'),
        ];
    }
}
