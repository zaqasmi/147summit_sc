<?php

namespace App\Filament\Resources\CapitalLiabilities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CapitalLiabilityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('source_label')
                    ->label('Source'),
                TextEntry::make('lender_name')
                    ->label('Bank / friend')
                    ->placeholder('-'),
                TextEntry::make('category'),
                TextEntry::make('principal_amount')
                    ->label('Total amount')
                    ->formatStateUsing(fn ($state): string => 'Rs ' . number_format((float) $state, 2)),
                TextEntry::make('paid_amount')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => 'Rs ' . number_format((float) $state, 2)),
                TextEntry::make('balance_amount')
                    ->label('Balance left')
                    ->formatStateUsing(fn ($state): string => 'Rs ' . number_format((float) $state, 2)),
                TextEntry::make('installment_amount')
                    ->label('Installment amount')
                    ->formatStateUsing(fn ($state): string => 'Rs ' . number_format((float) $state, 2)),
                TextEntry::make('installment_frequency')
                    ->label('Frequency'),
                TextEntry::make('due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status_label')
                    ->label('Status'),
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
