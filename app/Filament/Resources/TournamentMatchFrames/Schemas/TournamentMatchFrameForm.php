<?php

namespace App\Filament\Resources\TournamentMatchFrames\Schemas;

use App\Models\TournamentMatch;
use App\Models\TournamentPlayer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TournamentMatchFrameForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Frame score')
                    ->columns(3)
                    ->schema([
                        Select::make('tournament_match_id')
                            ->label('Match')
                            ->relationship('match', 'id')
                            ->getOptionLabelFromRecordUsing(fn (TournamentMatch $record): string => self::matchLabel($record))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('frame_number')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Select::make('winner_id')
                            ->label('Frame winner')
                            ->relationship('winner', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (TournamentPlayer $record): string => self::playerLabel($record))
                            ->searchable()
                            ->preload(),
                        TextInput::make('player1_score')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('player2_score')
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
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function matchLabel(TournamentMatch $match): string
    {
        return "#{$match->match_number} - {$match->round_name} - {$match->player1?->full_name} vs {$match->player2?->full_name}";
    }

    private static function playerLabel(TournamentPlayer $player): string
    {
        $registration = filled($player->registration_number) ? " ({$player->registration_number})" : '';

        return $player->full_name.$registration;
    }
}
