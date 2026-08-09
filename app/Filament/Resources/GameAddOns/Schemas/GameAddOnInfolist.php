<?php

namespace App\Filament\Resources\GameAddOns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameAddOnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('gameSession.id')
                    ->label('Game session'),
                TextEntry::make('addOnItem.name')
                    ->label('Add on item')
                    ->placeholder('-'),
                TextEntry::make('item_name'),
                TextEntry::make('unit_price')
                    ->money(),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('total_amount')
                    ->numeric(),
                TextEntry::make('charged_to')
                    ->label('Charged to'),
                TextEntry::make('charged_player_labels')
                    ->label('Paying player(s)'),
                TextEntry::make('charged_player_payment_status')
                    ->label('Player status / balance')
                    ->columnSpanFull(),
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
