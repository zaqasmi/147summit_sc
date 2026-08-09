<?php

namespace App\Filament\Resources\SnookerTables\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SnookerTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('number')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(99),
                TextInput::make('name')
                    ->required(),
                TextInput::make('hourly_rate')
                    ->label('Century minute rate')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(10),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
