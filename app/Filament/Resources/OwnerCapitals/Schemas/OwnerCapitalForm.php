<?php

namespace App\Filament\Resources\OwnerCapitals\Schemas;

use App\Models\OwnerCapital;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OwnerCapitalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner Capital')
                    ->icon('heroicon-o-currency-rupee')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        DatePicker::make('entry_date')
                            ->default(today())
                            ->required(),
                        Select::make('type')
                            ->options(OwnerCapital::typeOptions())
                            ->required()
                            ->default('investment'),
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal'),
                        TextInput::make('description')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
