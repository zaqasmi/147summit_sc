<?php

namespace App\Filament\Resources\Staff\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('role')
                    ->required()
                    ->default('manager'),
                TextInput::make('commission_rate')
                    ->label('Distribution weight')
                    ->helperText('Used to split the monthly commission pool. Use 0 for staff who are paid as salary or expense, not commission.')
                    ->required()
                    ->numeric()
                    ->default(25),
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
