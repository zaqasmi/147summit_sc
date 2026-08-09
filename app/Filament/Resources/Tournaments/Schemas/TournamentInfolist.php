<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TournamentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('type'),
                TextEntry::make('starts_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('registration_closes_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('registration_fee')
                    ->numeric(),
                TextEntry::make('max_players')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('rules')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('match_format'),
                TextEntry::make('status'),
                TextEntry::make('draw_generated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('prize_notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_published')
                    ->boolean(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
