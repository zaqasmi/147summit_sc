<?php

namespace App\Filament\Resources\CustomerDues\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerDueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Due')
                    ->icon('heroicon-o-users')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Customer name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->maxLength(255),
                        TextInput::make('opening_balance')
                            ->label('Opening balance')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->default(0),
                        TextInput::make('total_charged')
                            ->label('Dues added from closings')
                            ->prefix('Rs')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('total_paid')
                            ->label('Paid')
                            ->prefix('Rs')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('total_discounted')
                            ->label('Discounted')
                            ->prefix('Rs')
                            ->readOnly()
                            ->dehydrated(false),
                        TextInput::make('balance_due')
                            ->label('Balance due')
                            ->prefix('Rs')
                            ->readOnly()
                            ->dehydrated(false),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
