<?php

namespace App\Filament\Resources\OwnerCapitals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OwnerCapitalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('entry_date')
                    ->date(),
                TextEntry::make('type_label')
                    ->label('Type'),
                TextEntry::make('source_label')
                    ->label('Source'),
                TextEntry::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('signed_amount')
                    ->label('Capital effect')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('description')
                    ->placeholder('-')
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
