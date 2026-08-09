<?php

namespace App\Filament\Resources\TournamentMatchFrames;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\TournamentMatchFrames\Pages\CreateTournamentMatchFrame;
use App\Filament\Resources\TournamentMatchFrames\Pages\EditTournamentMatchFrame;
use App\Filament\Resources\TournamentMatchFrames\Pages\ListTournamentMatchFrames;
use App\Filament\Resources\TournamentMatchFrames\Pages\ViewTournamentMatchFrame;
use App\Filament\Resources\TournamentMatchFrames\Schemas\TournamentMatchFrameForm;
use App\Filament\Resources\TournamentMatchFrames\Schemas\TournamentMatchFrameInfolist;
use App\Filament\Resources\TournamentMatchFrames\Tables\TournamentMatchFramesTable;
use App\Models\TournamentMatchFrame;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TournamentMatchFrameResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = TournamentMatchFrame::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Frame Scores';

    protected static string|\UnitEnum|null $navigationGroup = 'Tournament Management';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TournamentMatchFrameForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TournamentMatchFrameInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentMatchFramesTable::configure($table);
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
            'index' => ListTournamentMatchFrames::route('/'),
            'create' => CreateTournamentMatchFrame::route('/create'),
            'view' => ViewTournamentMatchFrame::route('/{record}'),
            'edit' => EditTournamentMatchFrame::route('/{record}/edit'),
        ];
    }
}
