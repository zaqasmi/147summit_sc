<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Models\Tournament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->helperText('Leave blank to auto-generate from tournament name.'),
                Select::make('type')
                    ->options(Tournament::typeOptions())
                    ->required()
                    ->default('knockout'),
                DatePicker::make('starts_at'),
                DatePicker::make('ends_at'),
                DateTimePicker::make('registration_closes_at'),
                TextInput::make('registration_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_players')
                    ->numeric(),
                Textarea::make('rules')
                    ->rows(6)
                    ->columnSpanFull(),
                Select::make('match_format')
                    ->options(Tournament::matchFormatOptions())
                    ->required()
                    ->default('best_of_5'),
                Select::make('status')
                    ->options(Tournament::statusOptions())
                    ->required()
                    ->default('upcoming'),
                DateTimePicker::make('draw_generated_at'),
                Textarea::make('prize_notes')
                    ->columnSpanFull(),
                Toggle::make('is_featured')
                    ->default(false)
                    ->required(),
                Toggle::make('is_published')
                    ->default(true)
                    ->required(),
                Hidden::make('created_by')
                    ->default(fn (): ?int => auth()->id()),
            ]);
    }
}
