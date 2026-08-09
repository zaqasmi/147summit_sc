<?php

namespace App\Filament\Resources\ClubSettings;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\ClubSettings\Pages\CreateClubSetting;
use App\Filament\Resources\ClubSettings\Pages\EditClubSetting;
use App\Filament\Resources\ClubSettings\Pages\ListClubSettings;
use App\Filament\Resources\ClubSettings\Pages\ViewClubSetting;
use App\Filament\Resources\ClubSettings\Schemas\ClubSettingForm;
use App\Filament\Resources\ClubSettings\Schemas\ClubSettingInfolist;
use App\Filament\Resources\ClubSettings\Tables\ClubSettingsTable;
use App\Models\ClubSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClubSettingResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = ClubSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Club Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return ClubSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClubSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClubSettingsTable::configure($table);
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
            'index' => ListClubSettings::route('/'),
            'create' => CreateClubSetting::route('/create'),
            'view' => ViewClubSetting::route('/{record}'),
            'edit' => EditClubSetting::route('/{record}/edit'),
        ];
    }
}
