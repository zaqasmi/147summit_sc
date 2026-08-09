<?php

namespace App\Filament\Resources\GameSessions\Tables;

use App\Filament\Support\CenturyTime;
use App\Filament\Support\GameSessionActions;
use App\Filament\Support\TableSummaries;
use App\Models\GameParticipant;
use App\Models\GameSession;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('started_at', 'desc')
            ->columns([
                TextColumn::make('snookerTable.name')
                    ->label('Table')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('game_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one_to_one' => 'One to one',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                        default => ucfirst($state),
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'checked_out' => 'success',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('century_time')
                    ->label('Century time')
                    ->getStateUsing(fn (GameSession $record): HtmlString => CenturyTime::html($record))
                    ->html(),
                TextInputColumn::make('frames_played')
                    ->label('Total frames')
                    ->type('number')
                    ->rules(['integer', 'min:0'])
                    ->summarize(TableSummaries::numberTotal())
                    ->disabled(fn (GameSession $record): bool => ! (auth()->user()?->canManageGameSessions() ?? false) || $record->status !== 'active' || ! $record->isFrameGame())
                    ->updateStateUsing(function (GameSession $record, $state): int {
                        $frames = max(0, (int) $state);

                        $record->update(['frames_played' => $frames]);

                        return $frames;
                    })
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::participantMoneyTotal('total_due')),
                TextColumn::make('paid_total')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::participantMoneyTotal('amount_paid')),
                TextColumn::make('outstanding_total')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::participantBalanceTotal())
                    ->color(fn ($state): string => ((float) $state > 0) ? 'danger' : 'success'),
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
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'checked_out' => 'Checked out',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('game_type')
                    ->label('Game type')
                    ->options([
                        'one_to_one' => 'One to one',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                    ]),
            ])
            ->recordActions([
                GameSessionActions::addFrame(),
                GameSessionActions::removeFrame(),
                GameSessionActions::addOn(),
                GameSessionActions::checkout(),
                GameSessionActions::collectPayment(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function participantMoneyTotal(string $column): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn ($query): float => (float) GameParticipant::query()
                ->whereIn('game_session_id', self::sessionIds($query))
                ->sum($column))
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }

    private static function participantBalanceTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(function ($query): float {
                $participants = GameParticipant::query()
                    ->whereIn('game_session_id', self::sessionIds($query));

                return max(
                    0,
                    (float) (clone $participants)->sum('total_due') - (float) $participants->sum('amount_paid'),
                );
            })
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }

    /**
     * @return array<int, int>
     */
    private static function sessionIds($query): array
    {
        return (clone $query)->pluck('id')->all();
    }
}
