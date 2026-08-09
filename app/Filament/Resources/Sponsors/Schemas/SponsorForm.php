<?php

namespace App\Filament\Resources\Sponsors\Schemas;

use App\Models\Sponsor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Partner profile')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('category')
                            ->options(Sponsor::categoryOptions())
                            ->required()
                            ->default('corporate_sponsor'),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('cms/sponsors'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
