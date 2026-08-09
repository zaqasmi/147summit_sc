<?php

namespace App\Filament\Resources\GameAddOns\Schemas;

use App\Models\AddOnItem;
use App\Models\GameSession;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GameAddOnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Add-on line')
                    ->columns(3)
                    ->schema([
                        Select::make('game_session_id')
                            ->label('Session')
                            ->relationship('gameSession', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->latest('started_at'))
                            ->getOptionLabelFromRecordUsing(fn (GameSession $record): string => "#{$record->id} - {$record->snookerTable?->name} - {$record->started_at?->format('d M h:i A')}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('add_on_item_id')
                            ->label('Item')
                            ->relationship('addOnItem', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $item = AddOnItem::find($state);

                                if ($item) {
                                    $set('item_name', $item->name);
                                    $set('unit_price', $item->unit_price);
                                }
                            }),
                        TextInput::make('item_name')
                            ->required(),
                        TextInput::make('unit_price')
                            ->required()
                            ->numeric()
                            ->prefix('Rs')
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->default(1),
                        TextInput::make('total_amount')
                            ->prefix('Rs')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('charged_to')
                            ->options([
                                'losers' => 'Losers / payers',
                                'team_a' => 'Team A',
                                'team_b' => 'Team B',
                                'all_players' => 'All players',
                                'specific_player' => 'Specific player',
                            ])
                            ->required()
                            ->default('losers')
                            ->live(),
                        Select::make('player_id')
                            ->label('Specific player')
                            ->relationship('player', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('charged_to') === 'specific_player'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
