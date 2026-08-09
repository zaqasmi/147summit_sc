<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\GameParticipant;
use App\Models\GameSession;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment')
                    ->columns(3)
                    ->schema([
                        Select::make('game_participant_id')
                            ->label('Player charge')
                            ->relationship('participant', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->with(['player', 'gameSession.snookerTable'])->latest('updated_at'))
                            ->getOptionLabelFromRecordUsing(fn (GameParticipant $record): string => "#{$record->id} - {$record->player_label} - {$record->gameSession?->snookerTable?->name} - Balance Rs " . number_format($record->outstanding_amount, 2))
                            ->searchable()
                            ->preload(),
                        Select::make('game_session_id')
                            ->label('Session')
                            ->relationship('gameSession', 'id', modifyQueryUsing: fn (Builder $query): Builder => $query->latest('started_at'))
                            ->getOptionLabelFromRecordUsing(fn (GameSession $record): string => "#{$record->id} - {$record->snookerTable?->name} - {$record->started_at?->format('d M h:i A')}")
                            ->searchable()
                            ->preload(),
                        Select::make('player_id')
                            ->relationship('player', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('collected_by_staff_id')
                            ->label('Collected by')
                            ->relationship('collectedBy', 'name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('payment_date')
                            ->default(today())
                            ->required(),
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank' => 'Bank',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('cash'),
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
