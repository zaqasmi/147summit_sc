<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Support\CenturyTime;
use App\Filament\Support\GameSessionActions;
use App\Models\GameSession;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ActiveGames extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Active Table Games')
            ->description('Only currently running table sessions are shown here.')
            ->query(fn (): Builder => GameSession::query()
                ->with(['snookerTable', 'participants.player', 'participants.payments', 'addOns'])
                ->where('status', 'active'))
            ->defaultSort('started_at', 'desc')
            ->poll('10s')
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('snookerTable.name')
                    ->label('Table')
                    ->badge()
                    ->sortable(),
                TextColumn::make('players')
                    ->label('Players')
                    ->getStateUsing(fn (GameSession $record): string => $record->participants
                        ->map(fn ($participant): string => $participant->player_label . ($participant->team === 'solo' ? '' : " ({$participant->team})"))
                        ->join(', '))
                    ->wrap(),
                TextColumn::make('game_type')
                    ->label('Game')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one_to_one' => 'Solo',
                        'doubles' => 'Doubles',
                        'century' => 'Century',
                        default => ucfirst($state),
                    }),
                TextColumn::make('started_at')
                    ->label('Start')
                    ->time(),
                TextColumn::make('century_time')
                    ->label('Century time')
                    ->getStateUsing(fn (GameSession $record): HtmlString => CenturyTime::html($record))
                    ->html(),
                TextInputColumn::make('frames_played')
                    ->label('Total frames')
                    ->type('number')
                    ->rules(['integer', 'min:0'])
                    ->disabled(fn (GameSession $record): bool => ! $this->canManageGames() || $record->status !== 'active' || ! $record->isFrameGame())
                    ->updateStateUsing(function (GameSession $record, $state): int {
                        $frames = max(0, (int) $state);

                        $record->update(['frames_played' => $frames]);

                        return $frames;
                    }),
                TextColumn::make('add_on_total')
                    ->label('Add-ons')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('grand_total')
                    ->label('Due')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('paid_total')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => $this->money($state)),
                TextColumn::make('outstanding_total')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state): string => $this->money($state))
                    ->color(fn ($state): string => ((float) $state > 0) ? 'danger' : 'success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('newSession')
                    ->label('New session')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->visible(fn (): bool => $this->canManageGames())
                    ->url(GameSessionResource::getUrl('create')),
            ])
            ->recordActions([
                GameSessionActions::addFrame(),
                GameSessionActions::removeFrame(),
                GameSessionActions::addOn(),
                GameSessionActions::checkout(),
                GameSessionActions::collectPayment(),
            ]);
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }

    private function canManageGames(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
