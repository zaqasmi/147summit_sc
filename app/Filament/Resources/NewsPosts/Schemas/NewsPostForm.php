<?php

namespace App\Filament\Resources\NewsPosts\Schemas;

use App\Filament\Support\PublicFileUploadPreview;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Article')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->helperText('Leave blank to auto-generate from article title.')
                            ->maxLength(255),
                        Textarea::make('excerpt')
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->rows(10)
                            ->columnSpanFull(),
                        FileUpload::make('cover_image_path')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('cms/news')
                            ->imagePreviewHeight('160')
                            ->openable()
                            ->downloadable()
                            ->getUploadedFileUsing(PublicFileUploadPreview::currentHost()),
                    ]),
                Section::make('Publishing')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('published_at'),
                        Toggle::make('is_featured')
                            ->required()
                            ->default(false),
                        Toggle::make('is_published')
                            ->required()
                            ->default(true),
                    ]),
            ]);
    }
}
