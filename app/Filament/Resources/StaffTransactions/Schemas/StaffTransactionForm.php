<?php

namespace App\Filament\Resources\StaffTransactions\Schemas;

use App\Models\Staff;
use App\Models\StaffTransaction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class StaffTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Checkbox::make('split_between_all_staff')
                    ->label('All active commission staff')
                    ->helperText('Create one transaction per active staff member with a distribution weight above 0 and split this amount equally.')
                    ->live()
                    ->visible(fn (string $operation, Get $get): bool => $operation === 'create' && $get('type') !== 'salary')
                    ->dehydrated(fn (string $operation, Get $get): bool => $operation === 'create' && $get('type') !== 'salary'),
                Select::make('staff_id')
                    ->label('Staff')
                    ->disabled(fn (?StaffTransaction $record): bool => (bool) $record?->monthly_commission_id)
                    ->options(fn (?StaffTransaction $record): array => Staff::query()
                        ->where(fn ($query) => $query->where('is_active', true)->when($record, fn ($query) => $query->orWhere('id', $record->staff_id)))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => ($get('type') === 'salary' || ! (bool) $get('split_between_all_staff')))
                    ->hidden(fn (Get $get, string $operation): bool => $operation === 'create' && $get('type') !== 'salary' && (bool) $get('split_between_all_staff'))
                    ->dehydrated(fn (Get $get): bool => ($get('type') === 'salary' || ! (bool) $get('split_between_all_staff'))),
                DatePicker::make('transaction_date')
                    ->default(today())
                    ->required(),
                DatePicker::make('commission_month')
                    ->label(fn (Get $get): string => $get('type') === 'salary' ? 'Salary month' : 'Commission month')
                    ->disabled(fn (?StaffTransaction $record): bool => (bool) $record?->monthly_commission_id)
                    ->default(today()->startOfMonth()),
                Select::make('type')
                    ->disabled(fn (?StaffTransaction $record): bool => (bool) $record?->monthly_commission_id)
                    ->options(fn (?StaffTransaction $record): array => [
                        ...($record?->monthly_commission_id ? ['closing_payment' => 'Monthly closing payment'] : []),
                        'advance' => 'Advance paid',
                        'payout' => 'Commission payout',
                        'salary' => 'Salary payment',
                        'adjustment' => 'Adjustment',
                    ])
                    ->required()
                    ->helperText(fn (Get $get): ?string => $get('type') === 'salary' ? 'Owner-paid salary: reduces cash or bank funds without reducing business profit or staff commission.' : null)
                    ->default('advance')
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set): void {
                        if ($get('type') === 'salary') {
                            $set('split_between_all_staff', false);
                        }
                    }),
                Select::make('paid_from')
                    ->label('Paid from')
                    ->options(fn (?StaffTransaction $record): array => [...StaffTransaction::paidFromOptions(), ...($record?->paid_from === 'unrecorded' ? ['unrecorded' => 'Not recorded'] : [])])
                    ->required()
                    ->default('cash')
                    ->helperText('Collection payments reduce pending cash. RF Account and Saving Account payments debit the selected bank.'),
                TextInput::make('amount')
                    ->minValue(fn (?StaffTransaction $record, Get $get): ?float => $record?->monthly_commission_id || $get('type') === 'salary' ? 0.01 : null)
                    ->prefix('Rs')
                    ->required()
                    ->numeric(),
                TextInput::make('description'),
            ]);
    }
}
