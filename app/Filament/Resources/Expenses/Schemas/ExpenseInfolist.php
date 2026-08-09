<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('expense_date')
                    ->date(),
                TextEntry::make('staff.name')
                    ->label('Staff')
                    ->placeholder('-'),
                TextEntry::make('category'),
                TextEntry::make('description'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('paid_from'),
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
