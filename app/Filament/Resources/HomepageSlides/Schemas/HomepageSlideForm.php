<?php

namespace App\Filament\Resources\HomepageSlides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HomepageSlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Slide')
                    ->columns(3)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->maxLength(255),
                        FileUpload::make('image_path')
                            ->image()
                            ->disk('public')
                            ->directory('cms/slides'),
                        TextInput::make('button_label')
                            ->maxLength(255),
                        TextInput::make('button_url')
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
