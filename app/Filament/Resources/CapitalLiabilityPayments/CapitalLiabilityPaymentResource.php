<?php

namespace App\Filament\Resources\CapitalLiabilityPayments;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\CapitalLiabilityPayments\Pages\CreateCapitalLiabilityPayment;
use App\Filament\Resources\CapitalLiabilityPayments\Pages\EditCapitalLiabilityPayment;
use App\Filament\Resources\CapitalLiabilityPayments\Pages\ListCapitalLiabilityPayments;
use App\Filament\Resources\CapitalLiabilityPayments\Pages\ViewCapitalLiabilityPayment;
use App\Filament\Resources\CapitalLiabilityPayments\Schemas\CapitalLiabilityPaymentForm;
use App\Filament\Resources\CapitalLiabilityPayments\Schemas\CapitalLiabilityPaymentInfolist;
use App\Filament\Resources\CapitalLiabilityPayments\Tables\CapitalLiabilityPaymentsTable;
use App\Models\CapitalLiabilityPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CapitalLiabilityPaymentResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = CapitalLiabilityPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Capital Installments';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CapitalLiabilityPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CapitalLiabilityPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CapitalLiabilityPaymentsTable::configure($table);
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
            'index' => ListCapitalLiabilityPayments::route('/'),
            'create' => CreateCapitalLiabilityPayment::route('/create'),
            'view' => ViewCapitalLiabilityPayment::route('/{record}'),
            'edit' => EditCapitalLiabilityPayment::route('/{record}/edit'),
        ];
    }
}
