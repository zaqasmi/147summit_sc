<?php

namespace App\Filament\Resources\BankTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BankTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('entry_side_label')
                    ->label('Movement'),
                TextEntry::make('type_label')
                    ->label('Type'),
                TextEntry::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('signed_amount')
                    ->label('Bank effect')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('source_label')
                    ->label('Source'),
                TextEntry::make('description')
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
