<?php

namespace App\Filament\Resources\MonthlyClosings;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\MonthlyClosings\Pages\CreateMonthlyClosing;
use App\Filament\Resources\MonthlyClosings\Pages\EditMonthlyClosing;
use App\Filament\Resources\MonthlyClosings\Pages\ListMonthlyClosings;
use App\Filament\Resources\MonthlyClosings\Pages\ViewMonthlyClosing;
use App\Filament\Resources\MonthlyClosings\Schemas\MonthlyClosingForm;
use App\Filament\Resources\MonthlyClosings\Schemas\MonthlyClosingInfolist;
use App\Filament\Resources\MonthlyClosings\Tables\MonthlyClosingsTable;
use App\Models\MonthlyClosing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MonthlyClosingResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = MonthlyClosing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Monthly Closings';

    protected static string|\UnitEnum|null $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return MonthlyClosingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MonthlyClosingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonthlyClosingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlyClosings::route('/'),
            'create' => CreateMonthlyClosing::route('/create'),
            'view' => ViewMonthlyClosing::route('/{record}'),
            'edit' => EditMonthlyClosing::route('/{record}/edit'),
        ];
    }
}
