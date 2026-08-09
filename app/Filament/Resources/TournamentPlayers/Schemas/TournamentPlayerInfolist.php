<?php

namespace App\Filament\Resources\TournamentPlayers\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TournamentPlayerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tournament.name')
                    ->label('Tournament'),
                TextEntry::make('player.name')
                    ->label('Player')
                    ->placeholder('-'),
                TextEntry::make('full_name'),
                TextEntry::make('father_name')
                    ->placeholder('-'),
                ImageEntry::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->placeholder('-'),
                TextEntry::make('club_name')
                    ->placeholder('-'),
                TextEntry::make('district')
                    ->placeholder('-'),
                TextEntry::make('contact_number')
                    ->placeholder('-'),
                TextEntry::make('cnic')
                    ->placeholder('-'),
                TextEntry::make('registration_number'),
                TextEntry::make('registration_fee')
                    ->numeric(),
                TextEntry::make('fee_status'),
                TextEntry::make('payment_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('seed')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('avoid_group')
                    ->placeholder('-'),
                TextEntry::make('ranking_points')
                    ->numeric(),
                TextEntry::make('remarks')
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
