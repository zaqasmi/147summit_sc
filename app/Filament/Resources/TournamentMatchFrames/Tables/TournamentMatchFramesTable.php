<?php

namespace App\Filament\Resources\TournamentMatchFrames\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\Tournament;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TournamentMatchFramesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('match.tournament.name')
                    ->label('Tournament')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('match.round_name')
                    ->label('Round')
                    ->searchable(),
                TextColumn::make('match.match_number')
                    ->label('Match #')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('frame_number')
                    ->label('Frame #')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('player1_score')
                    ->label('P1 score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('player2_score')
                    ->label('P2 score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('winner.full_name')
                    ->label('Winner')
                    ->searchable(),
                TextColumn::make('player1_highest_break')
                    ->label('P1 high break')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('player2_highest_break')
                    ->label('P2 high break')
                    ->numeric()
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
                    ->options(fn (): array => Tournament::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(function (Builder $query, array $data): void {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereHas('match', fn (Builder $matchQuery): Builder => $matchQuery->where('tournament_id', $data['value']));
                    }),
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
