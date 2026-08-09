<?php

namespace App\Filament\Resources\Players\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\GameParticipant;
use App\Models\Player;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount()),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::balanceDueTotal())
                    ->color(fn ($state): string => ((float) $state > 0) ? 'danger' : 'success'),
                IconColumn::make('is_active')
                    ->boolean(),
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
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                Action::make('collectBalance')
                    ->label('Collect balance')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->schema([
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->numeric()
                            ->required()
                            ->default(fn (Player $record): float => $record->balance_due)
                            ->minValue(1),
                        DatePicker::make('payment_date')
                            ->default(today())
                            ->required(),
                        Select::make('collected_by_staff_id')
                            ->label('Collected by')
                            ->options(fn (): array => Staff::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->visible(fn (Player $record): bool => (auth()->user()?->isAdmin() ?? false) && $record->balance_due > 0)
                    ->action(function (Player $record, array $data): void {
                        $remaining = (float) $data['amount'];

                        $record->participants()
                            ->outstanding()
                            ->oldest()
                            ->get()
                            ->each(function ($participant) use (&$remaining, $data): void {
                                if ($remaining <= 0) {
                                    return;
                                }

                                $amount = min($remaining, $participant->outstanding_amount);

                                if ($amount <= 0) {
                                    return;
                                }

                                $participant->payments()->create([
                                    'game_session_id' => $participant->game_session_id,
                                    'player_id' => $participant->player_id,
                                    'collected_by_staff_id' => $data['collected_by_staff_id'] ?? null,
                                    'payment_date' => $data['payment_date'],
                                    'payment_method' => 'cash',
                                    'amount' => $amount,
                                ]);

                                $remaining -= $amount;
                            });

                        Notification::make()
                            ->title('Player balance collected')
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function balanceDueTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(function ($query): float {
                $playerIds = (clone $query)->pluck('id')->all();

                if ($playerIds === []) {
                    return 0.0;
                }

                $participants = GameParticipant::query()
                    ->whereIn('player_id', $playerIds)
                    ->whereIn('payment_status', ['unpaid', 'partial']);

                return max(
                    0,
                    (float) (clone $participants)->sum('total_due') - (float) $participants->sum('amount_paid'),
                );
            })
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }
}
