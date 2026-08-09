<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('gameSession.id')
                    ->label('Game session')
                    ->placeholder('-'),
                TextEntry::make('game_participant_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('player.name')
                    ->label('Player')
                    ->placeholder('-'),
                TextEntry::make('collected_by_staff_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payment_date')
                    ->date(),
                TextEntry::make('payment_method'),
                TextEntry::make('amount')
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
