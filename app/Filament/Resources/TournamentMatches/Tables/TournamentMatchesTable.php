<?php

namespace App\Filament\Resources\TournamentMatches\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TournamentMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('tournament.name')
                    ->label('Tournament')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('round_number')
                    ->label('Round #')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('round_name')
                    ->label('Round')
                    ->searchable(),
                TextColumn::make('match_number')
                    ->label('Match #')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('table_number')
                    ->label('Table')
                    ->searchable(),
                TextColumn::make('player1.full_name')
                    ->label('Player 1')
                    ->searchable(),
                TextColumn::make('player2.full_name')
                    ->label('Player 2')
                    ->searchable(),
                TextColumn::make('score_label')
                    ->label('Score')
                    ->badge()
                    ->color('info'),
                TextColumn::make('winner.full_name')
                    ->label('Winner')
                    ->searchable(),
                TextColumn::make('match_format')
                    ->label('Format')
                    ->formatStateUsing(fn (?string $state): string => Tournament::matchFormatOptions()[$state] ?? (string) $state)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TournamentMatch::statusOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'ongoing' => 'warning',
                        'completed', 'walkover' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('player1_highest_break')
                    ->label('P1 high break')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('player2_highest_break')
                    ->label('P2 high break')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->options(TournamentMatch::statusOptions()),
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
