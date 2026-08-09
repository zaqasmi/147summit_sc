<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;

class TableSummaries
{
    public static function recordCount(): Summarizer
    {
        return Summarizer::make()
            ->label('Records')
            ->using(fn ($query): int => (clone $query)->count());
    }

    public static function moneyTotal(): Sum
    {
        return Sum::make()
            ->label('Total')
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    public static function moneyAverage(): Average
    {
        return Average::make()
            ->label('Avg')
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    public static function numberTotal(int $decimals = 0): Sum
    {
        return Sum::make()
            ->label('Total')
            ->formatStateUsing(fn ($state): string => number_format((float) $state, $decimals));
    }

    public static function percentAverage(): Average
    {
        return Average::make()
            ->label('Avg')
            ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%');
    }

    public static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
