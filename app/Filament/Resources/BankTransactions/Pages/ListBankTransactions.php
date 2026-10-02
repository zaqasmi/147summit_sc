<?php

namespace App\Filament\Resources\BankTransactions\Pages;

use App\Filament\Concerns\ShowsCashBankBalances;
use App\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Models\BankTransaction;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class ListBankTransactions extends ListRecords
{
    use ShowsCashBankBalances;

    protected static string $resource = BankTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('depositPendingCash')
                ->label('Deposit pending cash')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('success')
                ->modalHeading('Record pending cash deposited to bank')
                ->modalSubmitActionLabel('Record deposit')
                ->disabled(fn (): bool => self::pendingCashAmount() <= 0)
                ->form([
                    Select::make('bank_account')
                        ->label('Deposit to')
                        ->options(BankTransaction::accountOptions())
                        ->default('rf_account')
                        ->required(),
                    DatePicker::make('transaction_date')
                        ->label('Deposit date')
                        ->default(today())
                        ->live()
                        ->afterStateUpdated(fn (Set $set, $state) => $set('amount', BankTransaction::availableCashForDeposit($state)))
                        ->required(),
                    TextInput::make('amount')
                        ->label('Amount deposited')
                        ->prefix('Rs')
                        ->numeric()
                        ->inputMode('decimal')
                        ->minValue(0.01)
                        ->maxValue(fn (Get $get): float => BankTransaction::availableCashForDeposit($get('transaction_date')))
                        ->default(fn (): float => self::pendingCashAmount())
                        ->required()
                        ->helperText('Defaults to the current cash pending bank amount. You can record a partial deposit.'),
                    TextInput::make('description')
                        ->default('Pending cash deposited to bank'),
                    TextInput::make('deposit_slip_number')
                        ->label('Deposit slip number')
                        ->maxLength(100),
                    DatePicker::make('deposit_slip_date')
                        ->label('Deposit slip date'),
                    Textarea::make('notes')
                        ->helperText('Use this if one bank deposit covers multiple daily closings.'),
                ])
                ->action(function (array $data): void {
                    if ((float) $data['amount'] > BankTransaction::availableCashForDeposit($data['transaction_date'])) {
                        throw ValidationException::withMessages(['amount' => 'The deposit exceeds cash available on the selected date.']);
                    }
                    BankTransaction::create([
                        'transaction_date' => $data['transaction_date'],
                        'type' => 'daily_collection_deposit',
                        'bank_account' => $data['bank_account'],
                        'amount' => round((float) $data['amount'], 2),
                        'deposit_slip_number' => $data['deposit_slip_number'] ?? null,
                        'deposit_slip_date' => $data['deposit_slip_date'] ?? null,
                        'description' => $data['description'] ?: 'Pending cash deposited to bank',
                        'notes' => $data['notes'] ?? null,
                    ]);
                })
                ->successNotificationTitle('Pending cash deposit recorded'),
            CreateAction::make(),
        ];
    }

    private static function pendingCashAmount(): float
    {
        return round((float) BankTransaction::summary()['collection_cash_pending_deposit'], 2);
    }
}
