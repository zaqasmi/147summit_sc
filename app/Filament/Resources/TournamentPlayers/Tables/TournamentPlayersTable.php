<?php

namespace App\Filament\Resources\TournamentPlayers\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\TournamentPlayer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TournamentPlayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('tournament.name')
                    ->label('Tournament')
                    ->searchable(),
                TextColumn::make('full_name')
                    ->label('Player')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('club_name')
                    ->searchable(),
                TextColumn::make('district')
                    ->searchable(),
                TextColumn::make('contact_number')
                    ->label('Contact')
                    ->searchable(),
                TextColumn::make('registration_number')
                    ->label('Reg #')
                    ->searchable(),
                TextColumn::make('registration_fee')
                    ->formatStateUsing(fn ($state): string => TableSummaries::money($state))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('fee_status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TournamentPlayer::feeStatusOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => $state === 'paid' ? 'success' : 'warning')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('seed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('avoid_group')
                    ->label('Avoid group')
                    ->searchable(),
                TextColumn::make('ranking_points')
                    ->label('Ranking')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('matches_played')
                    ->label('Played')
                    ->numeric(),
                TextColumn::make('matches_won')
                    ->label('Won')
                    ->numeric(),
                TextColumn::make('highest_break')
                    ->label('High break')
                    ->numeric(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tournament_id')
                    ->label('Tournament')
                    ->relationship('tournament', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('fee_status')
                    ->options(TournamentPlayer::feeStatusOptions()),
            ])
            ->recordActions([
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
