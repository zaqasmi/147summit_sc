<?php

namespace App\Filament\Resources\GameParticipants\Tables;

use App\Filament\Support\ParticipantPaymentAction;
use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GameParticipantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('player_label')
                    ->label('Player')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount()),
                TextColumn::make('gameSession.snookerTable.name')
                    ->label('Table')
                    ->searchable(),
                TextColumn::make('gameSession.game_type')
                    ->label('Game')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one_to_one' => 'One to one',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                        default => ucfirst($state),
                    }),
                TextColumn::make('team')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_loser')
                    ->label('Pays')
                    ->boolean(),
                TextColumn::make('base_amount')
                    ->label('Frames/Minutes')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('add_on_amount')
                    ->label('Add-ons')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_due')
                    ->label('Due')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
                SelectFilter::make('team')
                    ->options([
                        'solo' => 'Solo',
                        'A' => 'Team A',
                        'B' => 'Team B',
                    ]),
            ])
            ->recordActions([
                ParticipantPaymentAction::collect(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
