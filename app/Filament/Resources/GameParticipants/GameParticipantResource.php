<?php

namespace App\Filament\Resources\GameParticipants;

use App\Filament\Concerns\AdminOnlyMutations;
use App\Filament\Resources\GameParticipants\Pages\CreateGameParticipant;
use App\Filament\Resources\GameParticipants\Pages\EditGameParticipant;
use App\Filament\Resources\GameParticipants\Pages\ListGameParticipants;
use App\Filament\Resources\GameParticipants\Pages\ViewGameParticipant;
use App\Filament\Resources\GameParticipants\Schemas\GameParticipantForm;
use App\Filament\Resources\GameParticipants\Schemas\GameParticipantInfolist;
use App\Filament\Resources\GameParticipants\Tables\GameParticipantsTable;
use App\Models\GameParticipant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameParticipantResource extends Resource
{
    use AdminOnlyMutations;

    protected static ?string $model = GameParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Player Checkouts';

    protected static ?string $modelLabel = 'player charge';

    protected static ?string $pluralModelLabel = 'player checkouts';

    protected static string|\UnitEnum|null $navigationGroup = 'Club Manager Operations';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return GameParticipantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameParticipantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameParticipantsTable::configure($table);
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
            'index' => ListGameParticipants::route('/'),
            'create' => CreateGameParticipant::route('/create'),
            'view' => ViewGameParticipant::route('/{record}'),
            'edit' => EditGameParticipant::route('/{record}/edit'),
        ];
    }
}
