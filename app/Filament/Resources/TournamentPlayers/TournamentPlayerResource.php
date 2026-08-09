<?php

namespace App\Filament\Resources\TournamentPlayers;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\TournamentPlayers\Pages\CreateTournamentPlayer;
use App\Filament\Resources\TournamentPlayers\Pages\EditTournamentPlayer;
use App\Filament\Resources\TournamentPlayers\Pages\ListTournamentPlayers;
use App\Filament\Resources\TournamentPlayers\Pages\ViewTournamentPlayer;
use App\Filament\Resources\TournamentPlayers\Schemas\TournamentPlayerForm;
use App\Filament\Resources\TournamentPlayers\Schemas\TournamentPlayerInfolist;
use App\Filament\Resources\TournamentPlayers\Tables\TournamentPlayersTable;
use App\Models\TournamentPlayer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TournamentPlayerResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = TournamentPlayer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Tournament Players';

    protected static string|\UnitEnum|null $navigationGroup = 'Tournament Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TournamentPlayerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TournamentPlayerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentPlayersTable::configure($table);
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
            'index' => ListTournamentPlayers::route('/'),
            'create' => CreateTournamentPlayer::route('/create'),
            'view' => ViewTournamentPlayer::route('/{record}'),
            'edit' => EditTournamentPlayer::route('/{record}/edit'),
        ];
    }
}
