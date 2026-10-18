<?php

namespace App\Filament\Resources\TournamentDrawSettings;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\TournamentDrawSettings\Pages\CreateTournamentDrawSetting;
use App\Filament\Resources\TournamentDrawSettings\Pages\EditTournamentDrawSetting;
use App\Filament\Resources\TournamentDrawSettings\Pages\ListTournamentDrawSettings;
use App\Models\Tournament;
use App\Models\TournamentDrawSetting;
use App\Models\TournamentPlayer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentDrawSettingResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = TournamentDrawSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Draw Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Tournament Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tournament_id')
                ->label('Tournament')
                ->default(fn (): ?int => request()->integer('tournament_id') ?: null)
                ->options(fn (): array => Tournament::query()->where('type', 'knockout')->orderBy('name')->pluck('name', 'id')->all())
                ->unique(ignoreRecord: true)
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('separated_player_ids', [])),
            Select::make('separated_player_ids')
                ->label('Players to keep apart')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn (Get $get): array => TournamentPlayer::query()->where('tournament_id', $get('tournament_id'))->orderBy('full_name')->pluck('full_name', 'id')->all())
                ->helperText('These players cannot face each other in the first round. Their seeded positions may change to keep them apart. Later rounds depend on winners. Save settings before generating the draw.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('tournament.name')->label('Tournament')->searchable(),
            TextColumn::make('separated_players_count')->label('Players kept apart')->state(fn (TournamentDrawSetting $record): int => count($record->separated_player_ids ?? [])),
            TextColumn::make('updated_at')->dateTime(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTournamentDrawSettings::route('/'),
            'create' => CreateTournamentDrawSetting::route('/create'),
            'edit' => EditTournamentDrawSetting::route('/{record}/edit'),
        ];
    }
}
