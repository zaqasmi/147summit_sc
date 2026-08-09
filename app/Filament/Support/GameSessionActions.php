<?php

namespace App\Filament\Support;

use App\Models\AddOnItem;
use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class GameSessionActions
{
    public static function addFrame(): Action
    {
        return Action::make('addFrame')
            ->label('+ Frame')
            ->icon('heroicon-o-plus')
            ->color('gray')
            ->visible(fn (GameSession $record): bool => self::canManageGames() && $record->status === 'active' && $record->isFrameGame())
            ->action(function (GameSession $record): void {
                $record->update([
                    'frames_played' => (int) $record->frames_played + 1,
                ]);
            });
    }

    public static function removeFrame(): Action
    {
        return Action::make('removeFrame')
            ->label('- Frame')
            ->icon('heroicon-o-minus')
            ->color('gray')
            ->visible(fn (GameSession $record): bool => self::canManageGames() && $record->status === 'active' && $record->isFrameGame() && ((int) $record->frames_played > 0))
            ->action(function (GameSession $record): void {
                $record->update([
                    'frames_played' => max(0, (int) $record->frames_played - 1),
                ]);
            });
    }

    public static function addOn(): Action
    {
        return Action::make('addOn')
            ->label('Add-on')
            ->icon('heroicon-o-squares-plus')
            ->color('warning')
            ->modalWidth('xl')
            ->visible(fn (GameSession $record): bool => self::canManageGames() && $record->status === 'active')
            ->schema([
                Select::make('add_on_item_id')
                    ->label('Item')
                    ->options(fn (): array => AddOnItem::query()->active()->orderBy('name')->pluck('name', 'id')->all())
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
                    ->label('Item name')
                    ->required(),
                TextInput::make('unit_price')
                    ->prefix('Rs')
                    ->numeric()
                    ->required()
                    ->minValue(0),
                TextInput::make('quantity')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->default(1),
                Select::make('charged_to')
                    ->label('Charge to')
                    ->options([
                        'losers' => 'Loser / paying player(s)',
                        'team_a' => 'Team A',
                        'team_b' => 'Team B',
                        'all_players' => 'All players',
                    ])
                    ->default('losers')
                    ->required(),
            ])
            ->action(function (GameSession $record, array $data): void {
                $record->addOns()->create([
                    'add_on_item_id' => $data['add_on_item_id'] ?? null,
                    'item_name' => $data['item_name'],
                    'unit_price' => $data['unit_price'],
                    'quantity' => $data['quantity'],
                    'charged_to' => $data['charged_to'],
                ]);

                Notification::make()
                    ->title('Add-on recorded')
                    ->success()
                    ->send();
            });
    }

    public static function checkout(): Action
    {
        return Action::make('checkout')
            ->label('End session')
            ->icon('heroicon-o-stop-circle')
            ->color('success')
            ->schema(fn (GameSession $record): array => [
                ...match ($record->game_type) {
                    'doubles' => [
                        Select::make('losing_team')
                            ->label('Losing team')
                            ->options(fn (): array => self::teamOptions($record))
                            ->helperText('Both players in the selected team will be charged. Each player pays the full frame fee; add-ons are split between them.')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ],
                    'century' => [
                        Select::make('loser_participant_id')
                            ->label('Century loser')
                            ->options(fn (): array => self::participantOptions($record))
                            ->helperText('Select the one loser. This player pays the full century time charge and add-ons charged to losers.')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ],
                    default => [
                        Select::make('loser_participant_ids')
                            ->label('Loser player(s)')
                            ->options(fn (): array => self::participantOptions($record))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(),
                    ],
                },
                DateTimePicker::make('ended_at')
                    ->label('System end time')
                    ->seconds(false)
                    ->default(now())
                    ->disabled()
                    ->dehydrated(false),
            ])
            ->visible(fn (GameSession $record): bool => self::canManageGames() && $record->status === 'active')
            ->action(function (GameSession $record, array $data): void {
                $record->participants()->update(['is_loser' => false]);

                if ($record->game_type === 'doubles') {
                    $record->participants()
                        ->where('team', $data['losing_team'])
                        ->update(['is_loser' => true]);
                } elseif ($record->game_type === 'century') {
                    $record->participants()
                        ->whereKey((int) $data['loser_participant_id'])
                        ->update(['is_loser' => true]);
                } else {
                    $participantIds = collect($data['loser_participant_ids'] ?? [])
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    $record->participants()->whereKey($participantIds)->update(['is_loser' => true]);
                }

                $endedAt = now();

                $record->update([
                    'ended_at' => $endedAt,
                    'checked_out_at' => $endedAt,
                    'status' => 'checked_out',
                ]);

                Notification::make()
                    ->title('Session ended')
                    ->body('The amount due has been calculated from frames, losers, discounts, and add-ons.')
                    ->success()
                    ->send();
            });
    }

    public static function collectPayment(): Action
    {
        return Action::make('collectPayment')
            ->label('Payment')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->schema(fn (GameSession $record): array => [
                Html::make(fn (Get $get): HtmlString => PaymentCounter::sessionHtml(
                    $record,
                    (int) ($get('game_participant_id') ?: 0),
                    filled($get('discount_amount')) ? (float) $get('discount_amount') : null,
                ))
                    ->columnSpanFull(),
                Select::make('game_participant_id')
                    ->label('Player')
                    ->options(fn (): array => self::outstandingParticipantOptions($record))
                    ->default(fn (): ?int => $record->participants->first(fn (GameParticipant $participant): bool => $participant->outstanding_amount > 0)?->id)
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state): void {
                        $participant = GameParticipant::find($state);

                        if (! $participant) {
                            return;
                        }

                        $set('discount_amount', $participant->discount_amount);
                        $set('amount', PaymentCounter::balanceAfterDiscount($participant, (float) $participant->discount_amount));
                    })
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('discount_amount')
                    ->label('Discount')
                    ->prefix('Rs')
                    ->numeric()
                    ->minValue(0)
                    ->default(fn (): float => (float) ($record->participants->first(fn (GameParticipant $participant): bool => $participant->outstanding_amount > 0)?->discount_amount ?? 0))
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                        $participant = GameParticipant::find($get('game_participant_id'));

                        if ($participant) {
                            $set('amount', PaymentCounter::balanceAfterDiscount($participant, (float) $state));
                        }
                    }),
                Html::make(fn (Get $get): HtmlString => PaymentCounter::html(
                    self::selectedPaymentParticipant($record, $get),
                    (float) ($get('discount_amount') ?? 0),
                ))
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Cash to collect now')
                    ->prefix('Rs')
                    ->numeric()
                    ->required()
                    ->default(fn (): ?float => $record->participants->first(fn (GameParticipant $participant): bool => $participant->outstanding_amount > 0)?->outstanding_amount)
                    ->minValue(0),
                DatePicker::make('payment_date')
                    ->default(today())
                    ->required(),
                Select::make('collected_by_staff_id')
                    ->label('Collected by')
                    ->options(fn (): array => Staff::query()->active()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload(),
            ])
            ->visible(fn (GameSession $record): bool => self::canManageGames() && $record->outstanding_total > 0)
            ->action(function (GameSession $record, array $data): void {
                $participant = $record->participants()->findOrFail($data['game_participant_id']);
                $participant->update([
                    'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                ]);

                $participant->refresh();
                $amount = min((float) ($data['amount'] ?? 0), $participant->outstanding_amount);

                if ($amount > 0) {
                    $participant->payments()->create([
                        'game_session_id' => $record->id,
                        'player_id' => $participant->player_id,
                        'collected_by_staff_id' => $data['collected_by_staff_id'] ?? null,
                        'payment_date' => $data['payment_date'],
                        'payment_method' => 'cash',
                        'amount' => $amount,
                    ]);
                }

                Notification::make()
                    ->title($amount > 0 ? 'Payment collected' : 'Discount applied')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, string>
     */
    public static function participantOptions(GameSession $record): array
    {
        return $record->participants()
            ->with('player')
            ->get()
            ->mapWithKeys(fn (GameParticipant $participant): array => [
                $participant->id => $participant->player_label.self::teamSuffix($participant),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function teamOptions(GameSession $record): array
    {
        return $record->participants()
            ->with('player')
            ->whereIn('team', ['A', 'B'])
            ->get()
            ->groupBy('team')
            ->map(fn ($participants, string $team): string => 'Team '.$team.' - '.$participants
                ->map(fn (GameParticipant $participant): string => $participant->player_label)
                ->join(' & '))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function outstandingParticipantOptions(GameSession $record): array
    {
        return $record->participants()
            ->with('player')
            ->get()
            ->filter(fn (GameParticipant $participant): bool => $participant->outstanding_amount > 0)
            ->mapWithKeys(fn (GameParticipant $participant): array => [
                $participant->id => $participant->player_label.' - '.self::money($participant->outstanding_amount),
            ])
            ->all();
    }

    private static function teamSuffix(GameParticipant $participant): string
    {
        return $participant->team === 'solo' ? '' : " ({$participant->team})";
    }

    private static function selectedPaymentParticipant(GameSession $record, Get $get): ?GameParticipant
    {
        $participantId = $get('game_participant_id')
            ?: $record->participants->first(fn (GameParticipant $participant): bool => $participant->outstanding_amount > 0)?->id;

        if (! $participantId) {
            return null;
        }

        return GameParticipant::query()
            ->with(['gameSession', 'player'])
            ->find($participantId);
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }

    private static function canManageGames(): bool
    {
        return auth()->user()?->canManageGameSessions() ?? false;
    }
}
