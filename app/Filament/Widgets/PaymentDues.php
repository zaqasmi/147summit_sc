<?php

namespace App\Filament\Widgets;

use App\Filament\Support\ParticipantPaymentAction;
use App\Models\GameParticipant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PaymentDues extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Payment Due')
            ->description('Unpaid and partial player balances. In doubles, each unpaid losing player appears separately.')
            ->query(fn (): Builder => GameParticipant::query()
                ->with(['player', 'gameSession.snookerTable', 'gameSession.participants.player'])
                ->outstanding()
                ->whereColumn('amount_paid', '<', 'total_due'))
            ->defaultSort('updated_at', 'desc')
            ->poll('20s')
            ->paginated([5, 10, 25])
            ->columns([
                TextColumn::make('player_label')
                    ->label('Unpaid player')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('gameSession.snookerTable.name')
                    ->label('Table')
                    ->badge()
                    ->searchable(),
                TextColumn::make('game_session_id')
                    ->label('Session')
                    ->formatStateUsing(fn ($state): string => '#' . $state)
                    ->sortable(),
                TextColumn::make('gameSession.game_type')
                    ->label('Game')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one_to_one' => 'Solo',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                        default => ucfirst($state),
                    }),
                TextColumn::make('team')
                    ->badge()
                    ->sortable(),
                TextColumn::make('base_amount')
                    ->label('Frames/minutes')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('add_on_amount')
                    ->label('Add-ons')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('outstanding_amount')
                    ->label('Payment due')
                    ->formatStateUsing(fn ($state): string => $this->money($state))
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                    ]),
                SelectFilter::make('game_type')
                    ->label('Game')
                    ->options([
                        'one_to_one' => 'Solo',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query): Builder => $query->whereHas(
                                'gameSession',
                                fn (Builder $sessionQuery): Builder => $sessionQuery->where('game_type', $data['value']),
                            ),
                        );
                    }),
                SelectFilter::make('team')
                    ->options([
                        'solo' => 'Solo',
                        'A' => 'Team A',
                        'B' => 'Team B',
                    ]),
            ])
            ->recordActions([
                ParticipantPaymentAction::collect(),
            ]);
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }
}
