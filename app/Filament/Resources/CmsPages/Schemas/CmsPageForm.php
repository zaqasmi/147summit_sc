<?php

namespace App\Filament\Resources\CmsPages\Schemas;

use App\Models\CmsPage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CmsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page content')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->helperText('Leave blank to auto-generate from page title.')
                            ->maxLength(255),
                        Select::make('section')
                            ->options(CmsPage::sectionOptions())
                            ->required()
                            ->default('general'),
                        TextInput::make('sort_order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Textarea::make('excerpt')
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->rows(10)
                            ->columnSpanFull(),
                    ]),
                Section::make('Publishing')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_published')
                            ->required()
                            ->default(true),
                        TextInput::make('meta_title')
                            ->maxLength(255),
                        Textarea::make('meta_description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
