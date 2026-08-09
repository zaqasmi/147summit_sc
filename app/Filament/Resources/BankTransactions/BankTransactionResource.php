<?php

namespace App\Filament\Resources\BankTransactions;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\BankTransactions\Pages\CreateBankTransaction;
use App\Filament\Resources\BankTransactions\Pages\EditBankTransaction;
use App\Filament\Resources\BankTransactions\Pages\ListBankTransactions;
use App\Filament\Resources\BankTransactions\Pages\ViewBankTransaction;
use App\Filament\Resources\BankTransactions\Schemas\BankTransactionForm;
use App\Filament\Resources\BankTransactions\Schemas\BankTransactionInfolist;
use App\Filament\Resources\BankTransactions\Tables\BankTransactionsTable;
use App\Models\BankTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankTransactionResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = BankTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Bank Transactions';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return BankTransactionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BankTransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankTransactionsTable::configure($table);
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
            'index' => ListBankTransactions::route('/'),
            'create' => CreateBankTransaction::route('/create'),
            'view' => ViewBankTransaction::route('/{record}'),
            'edit' => EditBankTransaction::route('/{record}/edit'),
        ];
    }
}
