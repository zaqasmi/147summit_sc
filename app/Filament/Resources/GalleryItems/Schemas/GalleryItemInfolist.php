<?php

namespace App\Filament\Resources\GalleryItems\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class GalleryItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tournament.name')
                    ->label('Tournament')
                    ->placeholder('-'),
                TextEntry::make('title'),
                TextEntry::make('type'),
                TextEntry::make('album')
                    ->placeholder('-'),
                ImageEntry::make('file_path')
                    ->label('Image')
                    ->disk('public')
                    ->placeholder('-'),
                TextEntry::make('video_url')
                    ->placeholder('-'),
                TextEntry::make('caption')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('is_published')
                    ->boolean(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
