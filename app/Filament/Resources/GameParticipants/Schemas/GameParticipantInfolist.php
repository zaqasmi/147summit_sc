<?php

namespace App\Filament\Resources\GameParticipants\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameParticipantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('gameSession.id')
                    ->label('Game session'),
                TextEntry::make('player.name')
                    ->label('Player')
                    ->placeholder('-'),
                TextEntry::make('player_name_snapshot')
                    ->placeholder('-'),
                TextEntry::make('team'),
                IconEntry::make('is_loser')
                    ->boolean(),
                TextEntry::make('base_amount')
                    ->numeric(),
                TextEntry::make('discount_amount')
                    ->numeric(),
                TextEntry::make('add_on_amount')
                    ->numeric(),
                TextEntry::make('total_due')
                    ->numeric(),
                TextEntry::make('amount_paid')
                    ->numeric(),
                TextEntry::make('payment_status'),
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
