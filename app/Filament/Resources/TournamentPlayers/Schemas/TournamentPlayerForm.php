<?php

namespace App\Filament\Resources\TournamentPlayers\Schemas;

use App\Models\TournamentPlayer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TournamentPlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registration')
                    ->columns(3)
                    ->schema([
                        Select::make('tournament_id')
                            ->relationship('tournament', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('player_id')
                            ->relationship('player', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Optional. Select an existing player or enter a new name below.'),
                        TextInput::make('registration_number')
                            ->helperText('Leave blank to auto-generate.')
                            ->maxLength(255),
                        TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('father_name')
                            ->maxLength(255),
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->disk('public')
                            ->directory('tournaments/players'),
                    ]),
                Section::make('Player details')
                    ->columns(3)
                    ->schema([
                        TextInput::make('club_name')
                            ->maxLength(255),
                        TextInput::make('district')
                            ->maxLength(255),
                        TextInput::make('contact_number')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('cnic')
                            ->maxLength(255),
                        TextInput::make('seed')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('avoid_group')
                            ->helperText('Used by random draw to avoid same-club/same-group first-round matches where possible.')
                            ->maxLength(255),
                        TextInput::make('ranking_points')
                            ->numeric()
                            ->default(0),
                    ]),
                Section::make('Fee')
                    ->columns(3)
                    ->schema([
                        TextInput::make('registration_fee')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Select::make('fee_status')
                            ->options(TournamentPlayer::feeStatusOptions())
                            ->required()
                            ->default('unpaid'),
                        DatePicker::make('payment_date'),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
