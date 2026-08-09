<?php

namespace App\Filament\Resources\Sponsors;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\Sponsors\Pages\CreateSponsor;
use App\Filament\Resources\Sponsors\Pages\EditSponsor;
use App\Filament\Resources\Sponsors\Pages\ListSponsors;
use App\Filament\Resources\Sponsors\Pages\ViewSponsor;
use App\Filament\Resources\Sponsors\Schemas\SponsorForm;
use App\Filament\Resources\Sponsors\Schemas\SponsorInfolist;
use App\Filament\Resources\Sponsors\Tables\SponsorsTable;
use App\Models\Sponsor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SponsorResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = Sponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Partners & Sponsors';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SponsorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SponsorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SponsorsTable::configure($table);
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
            'index' => ListSponsors::route('/'),
            'create' => CreateSponsor::route('/create'),
            'view' => ViewSponsor::route('/{record}'),
            'edit' => EditSponsor::route('/{record}/edit'),
        ];
    }
}
