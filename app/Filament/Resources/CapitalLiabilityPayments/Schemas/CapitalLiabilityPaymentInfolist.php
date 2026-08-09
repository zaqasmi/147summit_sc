<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CapitalLiabilityPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('capitalLiability.title')
                    ->label('Loan / capital item'),
                TextEntry::make('capitalLiability.lender_name')
                    ->label('Bank / friend')
                    ->placeholder('-'),
                TextEntry::make('payment_date')
                    ->date(),
                TextEntry::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('paid_from_label')
                    ->label('Paid from'),
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
