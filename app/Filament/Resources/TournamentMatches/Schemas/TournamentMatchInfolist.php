<?php

namespace App\Filament\Resources\TournamentMatches\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TournamentMatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tournament.name')
                    ->label('Tournament'),
                TextEntry::make('parentMatch.id')
                    ->label('Parent match')
                    ->placeholder('-'),
                TextEntry::make('nextMatch.id')
                    ->label('Next match')
                    ->placeholder('-'),
                TextEntry::make('next_match_slot')
                    ->placeholder('-'),
                TextEntry::make('round_number')
                    ->numeric(),
                TextEntry::make('round_name'),
                TextEntry::make('match_number')
                    ->numeric(),
                TextEntry::make('table_number')
                    ->placeholder('-'),
                TextEntry::make('player1.id')
                    ->label('Player1')
                    ->placeholder('-'),
                TextEntry::make('player2.id')
                    ->label('Player2')
                    ->placeholder('-'),
                TextEntry::make('winner.id')
                    ->label('Winner')
                    ->placeholder('-'),
                TextEntry::make('match_format'),
                TextEntry::make('status'),
                TextEntry::make('player1_frames')
                    ->numeric(),
                TextEntry::make('player2_frames')
                    ->numeric(),
                TextEntry::make('player1_highest_break')
                    ->numeric(),
                TextEntry::make('player2_highest_break')
                    ->numeric(),
                TextEntry::make('scheduled_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('started_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('ended_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
