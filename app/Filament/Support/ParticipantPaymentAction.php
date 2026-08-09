<?php

namespace App\Filament\Support;

use App\Models\GameParticipant;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class ParticipantPaymentAction
{
    public static function collect(): Action
    {
        return Action::make('collectPayment')
            ->label('Collect')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalWidth('2xl')
            ->schema(fn (GameParticipant $record): array => [
                Html::make(fn (Get $get): HtmlString => PaymentCounter::sessionHtml(
                    $record->gameSession,
                    $record->id,
                    filled($get('discount_amount')) ? (float) $get('discount_amount') : null,
                ))
                    ->columnSpanFull(),
                TextInput::make('discount_amount')
                    ->label('Discount')
                    ->prefix('Rs')
                    ->numeric()
                    ->minValue(0)
                    ->default(fn (GameParticipant $record): float => (float) $record->discount_amount)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (GameParticipant $record, Set $set, $state): void {
                        $set('amount', PaymentCounter::balanceAfterDiscount($record, (float) $state));
                    }),
                Html::make(fn (Get $get): HtmlString => PaymentCounter::html(
                    $record,
                    (float) ($get('discount_amount') ?? $record->discount_amount),
                ))
                    ->columnSpanFull(),
                TextInput::make('amount')
                    ->label('Cash to collect now')
                    ->prefix('Rs')
                    ->numeric()
                    ->required()
                    ->default(fn (GameParticipant $record): float => $record->outstanding_amount)
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
            ->visible(fn (GameParticipant $record): bool => (auth()->user()?->isAdmin() ?? false) && $record->outstanding_amount > 0)
            ->action(function (GameParticipant $record, array $data): void {
                $record->update([
                    'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                ]);

                $record->refresh();
                $amount = min((float) ($data['amount'] ?? 0), $record->outstanding_amount);

                if ($amount > 0) {
                    $record->payments()->create([
                        'game_session_id' => $record->game_session_id,
                        'player_id' => $record->player_id,
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
}
