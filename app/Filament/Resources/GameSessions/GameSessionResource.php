<?php

namespace App\Filament\Resources\GameSessions;

use App\Filament\Concerns\AdminOnlyMutations;
use App\Filament\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Resources\GameSessions\Pages\EditGameSession;
use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Pages\ViewGameSession;
use App\Filament\Resources\GameSessions\Schemas\GameSessionForm;
use App\Filament\Resources\GameSessions\Schemas\GameSessionInfolist;
use App\Filament\Resources\GameSessions\Tables\GameSessionsTable;
use App\Models\GameSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameSessionResource extends Resource
{
    use AdminOnlyMutations;

    protected static ?string $model = GameSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Game Sessions';

    protected static ?string $modelLabel = 'game session';

    protected static ?string $pluralModelLabel = 'game sessions';

    protected static string|\UnitEnum|null $navigationGroup = 'Club Manager Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return GameSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameSessionsTable::configure($table);
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
            'index' => ListGameSessions::route('/'),
            'create' => CreateGameSession::route('/create'),
            'view' => ViewGameSession::route('/{record}'),
            'edit' => EditGameSession::route('/{record}/edit'),
        ];
    }
}
