<?php

namespace App\Filament\Resources\CommissionRates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommissionRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Commission Rate')
                    ->icon('heroicon-o-receipt-percent')
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->schema([
                        DatePicker::make('effective_from')
                            ->label('Effective from')
                            ->default(today())
                            ->native(false)
                            ->required(),
                        TextInput::make('rate')
                            ->label('Overall commission rate')
                            ->suffix('%')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->inputMode('decimal')
                            ->default(25),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
