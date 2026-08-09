<?php

namespace App\Filament\Resources\CashDeposits;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\CashDeposits\Pages\CreateCashDeposit;
use App\Filament\Resources\CashDeposits\Pages\EditCashDeposit;
use App\Filament\Resources\CashDeposits\Pages\ListCashDeposits;
use App\Filament\Resources\CashDeposits\Pages\ViewCashDeposit;
use App\Filament\Resources\CashDeposits\Schemas\CashDepositForm;
use App\Filament\Resources\CashDeposits\Schemas\CashDepositInfolist;
use App\Filament\Resources\CashDeposits\Tables\CashDepositsTable;
use App\Models\CashDeposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashDepositResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = CashDeposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Daily Closings';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CashDepositForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashDepositInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashDepositsTable::configure($table);
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
            'index' => ListCashDeposits::route('/'),
            'create' => CreateCashDeposit::route('/create'),
            'view' => ViewCashDeposit::route('/{record}'),
            'edit' => EditCashDeposit::route('/{record}/edit'),
        ];
    }
}
