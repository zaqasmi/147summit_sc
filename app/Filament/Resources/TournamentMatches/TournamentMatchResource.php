<?php

namespace App\Filament\Resources\TournamentMatches;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\TournamentMatches\Pages\CreateTournamentMatch;
use App\Filament\Resources\TournamentMatches\Pages\EditTournamentMatch;
use App\Filament\Resources\TournamentMatches\Pages\ListTournamentMatches;
use App\Filament\Resources\TournamentMatches\Pages\ViewTournamentMatch;
use App\Filament\Resources\TournamentMatches\Schemas\TournamentMatchForm;
use App\Filament\Resources\TournamentMatches\Schemas\TournamentMatchInfolist;
use App\Filament\Resources\TournamentMatches\Tables\TournamentMatchesTable;
use App\Models\TournamentMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TournamentMatchResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = TournamentMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Tournament Matches';

    protected static string|\UnitEnum|null $navigationGroup = 'Tournament Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TournamentMatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TournamentMatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentMatchesTable::configure($table);
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
            'index' => ListTournamentMatches::route('/'),
            'create' => CreateTournamentMatch::route('/create'),
            'view' => ViewTournamentMatch::route('/{record}'),
            'edit' => EditTournamentMatch::route('/{record}/edit'),
        ];
    }
}
