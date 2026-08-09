<?php

namespace App\Filament\Resources\TournamentMatches\Schemas;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentPlayer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TournamentMatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Match')
                    ->columns(4)
                    ->schema([
                        Select::make('tournament_id')
                            ->relationship('tournament', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('round_number')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('round_name')
                            ->required()
                            ->default('Round 1')
                            ->maxLength(255),
                        TextInput::make('match_number')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('table_number')
                            ->maxLength(255),
                        Select::make('match_format')
                            ->options(Tournament::matchFormatOptions())
                            ->required()
                            ->default('best_of_5'),
                        Select::make('status')
                            ->options(TournamentMatch::statusOptions())
                            ->required()
                            ->default('scheduled'),
                    ]),
                Section::make('Players and progression')
                    ->columns(3)
                    ->schema([
                        Select::make('player1_id')
                            ->label('Player 1')
                            ->relationship('player1', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (TournamentPlayer $record): string => self::playerLabel($record))
                            ->searchable()
                            ->preload(),
                        Select::make('player2_id')
                            ->label('Player 2')
                            ->relationship('player2', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (TournamentPlayer $record): string => self::playerLabel($record))
                            ->searchable()
                            ->preload(),
                        Select::make('winner_id')
                            ->label('Winner')
                            ->relationship('winner', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (TournamentPlayer $record): string => self::playerLabel($record))
                            ->searchable()
                            ->preload(),
                        Select::make('parent_match_id')
                            ->label('Parent match')
                            ->relationship('parentMatch', 'id')
                            ->getOptionLabelFromRecordUsing(fn (TournamentMatch $record): string => self::matchLabel($record))
                            ->searchable()
                            ->preload(),
                        Select::make('next_match_id')
                            ->label('Next match')
                            ->relationship('nextMatch', 'id')
                            ->getOptionLabelFromRecordUsing(fn (TournamentMatch $record): string => self::matchLabel($record))
                            ->searchable()
                            ->preload(),
                        Select::make('next_match_slot')
                            ->options([
                                'player1' => 'Player 1 slot',
                                'player2' => 'Player 2 slot',
                            ]),
                    ]),
                Section::make('Score')
                    ->columns(4)
                    ->schema([
                        TextInput::make('player1_frames')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('player2_frames')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('player1_highest_break')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('player2_highest_break')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
                Section::make('Schedule')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('scheduled_at'),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('ended_at'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function playerLabel(TournamentPlayer $player): string
    {
        $registration = filled($player->registration_number) ? " ({$player->registration_number})" : '';

        return $player->full_name.$registration;
    }

    private static function matchLabel(TournamentMatch $match): string
    {
        return "#{$match->match_number} - {$match->round_name}";
    }
}
