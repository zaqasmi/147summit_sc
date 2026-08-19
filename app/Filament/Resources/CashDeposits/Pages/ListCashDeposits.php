<?php

namespace App\Filament\Resources\CashDeposits\Pages;

use App\Filament\Resources\CashDeposits\CashDepositResource;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Support\StaffTransactionCreator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListCashDeposits extends ListRecords
{
    protected static string $resource = CashDepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('addStaffAdvance')
                ->label('Add staff advance')
                ->icon('heroicon-o-wallet')
                ->color('warning')
                ->modalHeading('Add staff advance')
                ->form([
                    Checkbox::make('split_between_all_staff')
                        ->label('All active staff')
                        ->helperText('Create one advance per active staff member and split this amount equally.')
                        ->live(),
                    Select::make('staff_id')
                        ->label('Staff')
                        ->options(fn (): array => Staff::query()
                            ->active()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get): bool => ! (bool) $get('split_between_all_staff'))
                        ->hidden(fn (Get $get): bool => (bool) $get('split_between_all_staff'))
                        ->dehydrated(fn (Get $get): bool => ! (bool) $get('split_between_all_staff')),
                    DatePicker::make('transaction_date')
                        ->label('Payment date')
                        ->default(today())
                        ->required(),
                    DatePicker::make('commission_month')
                        ->label('Commission month')
                        ->default(today()->startOfMonth())
                        ->helperText('Advance will be deducted from this month. Leave as the current month unless you are adjusting an older month.'),
                    Select::make('paid_from')
                        ->label('Paid from')
                        ->options(StaffTransaction::paidFromOptions())
                        ->default('cash')
                        ->required()
                        ->helperText('Cash reduces cash pending bank deposit. Bank creates a bank ledger debit.'),
                    TextInput::make('amount')
                        ->label('Advance amount')
                        ->prefix('Rs')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),
                    TextInput::make('description')
                        ->default('Staff advance'),
                ])
                ->action(function (array $data): void {
                    StaffTransactionCreator::create([
                        ...$data,
                        'type' => 'advance',
                    ]);
                })
                ->successNotificationTitle('Staff advance recorded'),
        ];
    }
}
