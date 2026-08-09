<?php

namespace App\Filament\Resources\AddOnItems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AddOnItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rs'),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
            ]);
    }
}
