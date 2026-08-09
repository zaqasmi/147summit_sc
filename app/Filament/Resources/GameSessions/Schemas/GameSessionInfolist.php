<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GameSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('snookerTable.name')
                    ->label('Snooker table'),
                TextEntry::make('game_type'),
                TextEntry::make('status'),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('ended_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('checked_out_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('frames_played')
                    ->numeric(),
                TextEntry::make('frame_fee')
                    ->numeric(),
                TextEntry::make('hourly_rate')
                    ->label('Century minute rate')
                    ->numeric(),
                TextEntry::make('discount_total')
                    ->numeric(),
                TextEntry::make('created_by')
                    ->numeric()
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
