<?php

namespace App\Filament\Resources\ManagementTeamMembers\Schemas;

use App\Filament\Support\PublicFileUploadPreview;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManagementTeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team member')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('role_title')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('cms/team')
                            ->imagePreviewHeight('160')
                            ->openable()
                            ->downloadable()
                            ->getUploadedFileUsing(PublicFileUploadPreview::currentHost()),
                        Textarea::make('bio')
                            ->columnSpanFull(),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
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
