<?php

namespace App\Filament\Resources\TournamentMatchFrames\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TournamentMatchFrameInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tournament_match_id')
                    ->numeric(),
                TextEntry::make('frame_number')
                    ->numeric(),
                TextEntry::make('player1_score')
                    ->numeric(),
                TextEntry::make('player2_score')
                    ->numeric(),
                TextEntry::make('winner.id')
                    ->label('Winner')
                    ->placeholder('-'),
                TextEntry::make('player1_highest_break')
                    ->numeric(),
                TextEntry::make('player2_highest_break')
                    ->numeric(),
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
