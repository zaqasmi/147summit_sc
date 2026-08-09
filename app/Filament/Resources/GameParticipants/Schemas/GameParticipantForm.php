<?php

namespace App\Filament\Resources\GameParticipants\Schemas;

use App\Models\GameSession;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GameParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Charge')
                    ->columns(3)
                    ->schema([
                        Select::make('game_session_id')
                            ->label('Session')
                            ->relationship('gameSession', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->latest('started_at'))
                            ->getOptionLabelFromRecordUsing(fn (GameSession $record): string => "#{$record->id} - {$record->snookerTable?->name} - {$record->started_at?->format('d M h:i A')}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('player_id')
                            ->relationship('player', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('player_name_snapshot')
                            ->label('Guest name'),
                        Select::make('team')
                            ->options([
                                'solo' => 'Solo',
                                'A' => 'Team A',
                                'B' => 'Team B',
                            ])
                            ->required()
                            ->default('solo'),
                        Toggle::make('is_loser')
                            ->label('Pays for game/add-ons')
                            ->required(),
                    ]),
                Section::make('Computed totals')
                    ->columns(4)
                    ->schema([
                        TextInput::make('base_amount')
                            ->prefix('Rs')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('add_on_amount')
                            ->prefix('Rs')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('total_due')
                            ->prefix('Rs')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('amount_paid')
                            ->prefix('Rs')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('payment_status')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
