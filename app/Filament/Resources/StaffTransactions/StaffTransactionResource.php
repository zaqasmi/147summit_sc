<?php

namespace App\Filament\Resources\StaffTransactions;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\StaffTransactions\Pages\CreateStaffTransaction;
use App\Filament\Resources\StaffTransactions\Pages\EditStaffTransaction;
use App\Filament\Resources\StaffTransactions\Pages\ListStaffTransactions;
use App\Filament\Resources\StaffTransactions\Pages\ViewStaffTransaction;
use App\Filament\Resources\StaffTransactions\Schemas\StaffTransactionForm;
use App\Filament\Resources\StaffTransactions\Schemas\StaffTransactionInfolist;
use App\Filament\Resources\StaffTransactions\Tables\StaffTransactionsTable;
use App\Models\StaffTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffTransactionResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = StaffTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Staff Transactions';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return StaffTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaffTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTransactionsTable::configure($table);
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
            'index' => ListStaffTransactions::route('/'),
            'create' => CreateStaffTransaction::route('/create'),
            'view' => ViewStaffTransaction::route('/{record}'),
            'edit' => EditStaffTransaction::route('/{record}/edit'),
        ];
    }
}
