<?php

namespace App\Filament\Resources\Players;

use App\Filament\Concerns\AdminOnlyMutations;
use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Pages\ViewPlayer;
use App\Filament\Resources\Players\Schemas\PlayerForm;
use App\Filament\Resources\Players\Schemas\PlayerInfolist;
use App\Filament\Resources\Players\Tables\PlayersTable;
use App\Models\Player;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlayerResource extends Resource
{
    use AdminOnlyMutations;

    protected static ?string $model = Player::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Club Manager Operations';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return PlayerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlayerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayersTable::configure($table);
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
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'view' => ViewPlayer::route('/{record}'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }
}
