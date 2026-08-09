<?php

namespace App\Filament\Resources\ClubSettings\Schemas;

use App\Models\ClubSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClubSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting')
                    ->columns(3)
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('group')
                            ->required()
                            ->default('general')
                            ->maxLength(255),
                        Select::make('type')
                            ->options(ClubSetting::typeOptions())
                            ->required()
                            ->default('text'),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Textarea::make('value')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
