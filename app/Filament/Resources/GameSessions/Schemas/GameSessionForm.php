<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Models\AddOnItem;
use App\Models\GameSession;
use App\Models\SnookerTable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GameSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Start game')
                    ->icon('heroicon-o-play-circle')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('snooker_table_id')
                            ->label('Table')
                            ->relationship(
                                'snookerTable',
                                'name',
                                modifyQueryUsing: fn (Builder $query, ?GameSession $record = null): Builder => $query
                                    ->where('is_active', true)
                                    ->whereDoesntHave('gameSessions', function (Builder $sessionQuery) use ($record): void {
                                        $sessionQuery->where('status', 'active');

                                        if ($record?->exists) {
                                            $sessionQuery->whereKeyNot($record->getKey());
                                        }
                                    })
                                    ->orderBy('number'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (SnookerTable $record): string => "{$record->name} (Table {$record->number})")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $table = SnookerTable::find($state);

                                if ($table) {
                                    $set('hourly_rate', $table->hourly_rate);
                                }
                            }),
                        Select::make('game_type')
                            ->label('Game type')
                            ->options([
                                'one_to_one' => 'Solo / one to one',
                                'doubles' => 'Doubles',
                                'century' => 'Century game',
                            ])
                            ->required()
                            ->default('one_to_one')
                            ->live(),
                        DateTimePicker::make('started_at')
                            ->label('Auto date & time')
                            ->seconds(false)
                            ->default(now())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('frames_played')
                            ->label('Total frames')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->inputMode('numeric')
                            ->hidden(fn (Get $get): bool => $get('game_type') === 'century'),
                        Hidden::make('status')
                            ->default('active'),
                        Hidden::make('frame_fee')
                            ->default(100),
                        DateTimePicker::make('ended_at')
                            ->seconds(false)
                            ->hidden(),
                        DateTimePicker::make('checked_out_at')
                            ->seconds(false)
                            ->hidden(),
                        TextInput::make('hourly_rate')
                            ->label('Century minute rate')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->inputMode('decimal')
                            ->visible(fn (Get $get): bool => $get('game_type') === 'century'),
                        Repeater::make('participants')
                            ->relationship()
                            ->columnSpanFull()
                            ->defaultItems(2)
                            ->columns([
                                'default' => 1,
                                'lg' => 5,
                            ])
                            ->collapsible()
                            ->addActionLabel('Add player')
                            ->schema([
                                Select::make('player_id')
                                    ->label('Player')
                                    ->relationship('player', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'))
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('phone')->tel(),
                                    ])
                                    ->columnSpan(2),
                                TextInput::make('player_name_snapshot')
                                    ->label('Guest name')
                                    ->placeholder('Only if not saved as player')
                                    ->columnSpan(2),
                                Select::make('team')
                                    ->label('Side')
                                    ->options([
                                        'solo' => 'Solo',
                                        'A' => 'Team A',
                                        'B' => 'Team B',
                                    ])
                                    ->default(fn (Get $get): string => $get('../../game_type') === 'doubles' ? 'A' : 'solo')
                                    ->required(),
                                Hidden::make('base_amount')
                                    ->dehydrated(false),
                                Hidden::make('add_on_amount')
                                    ->dehydrated(false),
                                Hidden::make('total_due')
                                    ->dehydrated(false),
                                Hidden::make('amount_paid')
                                    ->dehydrated(false),
                            ]),
                    ]),
                Section::make('Add-ons')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
                        Repeater::make('addOns')
                            ->relationship()
                            ->columns([
                                'default' => 1,
                                'lg' => 6,
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->addActionLabel('Add tea, drink, snack')
                            ->schema([
                                Select::make('add_on_item_id')
                                    ->label('Item')
                                    ->relationship('addOnItem', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('unit_price')->prefix('Rs')->numeric()->required(),
                                    ])
                                    ->afterStateUpdated(function (Set $set, $state): void {
                                        $item = AddOnItem::find($state);

                                        if ($item) {
                                            $set('item_name', $item->name);
                                            $set('unit_price', $item->unit_price);
                                        }
                                    })
                                    ->columnSpan(2),
                                TextInput::make('item_name')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('unit_price')
                                    ->prefix('Rs')
                                    ->numeric()
                                    ->minValue(0)
                                    ->inputMode('decimal')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->inputMode('decimal')
                                    ->default(1)
                                    ->required(),
                                Select::make('charged_to')
                                    ->options([
                                        'losers' => 'Losers / payers',
                                        'team_a' => 'Team A',
                                        'team_b' => 'Team B',
                                        'all_players' => 'All players',
                                        'specific_player' => 'Specific player',
                                    ])
                                    ->default('losers')
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),
                                Select::make('player_id')
                                    ->label('Specific player')
                                    ->relationship('player', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Get $get): bool => $get('charged_to') === 'specific_player')
                                    ->columnSpan(2),
                                Textarea::make('notes')
                                    ->columnSpan(2),
                            ]),
                    ]),
                Section::make('Notes')
                    ->icon('heroicon-o-pencil-square')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
