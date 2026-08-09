<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use App\Models\GalleryItem;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GalleryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gallery item')
                    ->columns(3)
                    ->schema([
                        Select::make('tournament_id')
                            ->relationship('tournament', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options(GalleryItem::typeOptions())
                            ->required()
                            ->default('image'),
                        TextInput::make('album')
                            ->maxLength(255),
                        FileUpload::make('file_path')
                            ->label('Image/file')
                            ->image()
                            ->disk('public')
                            ->directory('cms/gallery'),
                        TextInput::make('video_url')
                            ->url()
                            ->maxLength(255),
                        Textarea::make('caption')
                            ->columnSpanFull(),
                        Toggle::make('is_published')
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
