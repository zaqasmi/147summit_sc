<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Services\ReportService;
use App\Support\StaffTransactionCreator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyCommissions extends ListRecords
{
    protected static string $resource = MonthlyCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateMonth')
                ->label('Generate month')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Generate monthly staff commission balances')
                ->modalSubmitActionLabel('Generate')
                ->form([
                    DatePicker::make('month')
                        ->label('Commission month')
                        ->default(today()->startOfMonth())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $records = app(ReportService::class)->generateMonthlyCommissions($data['month']);

                    Notification::make()
                        ->title('Monthly commission balances updated')
                        ->body($records->count().' staff commission record(s) generated or refreshed.')
                        ->success()
                        ->send();
                }),
            Action::make('recordCommissionPayout')
                ->label('Record payout')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->modalHeading('Record staff commission payout')
                ->modalSubmitActionLabel('Record payout')
                ->form([
                    Select::make('staff_id')
                        ->label('Staff')
                        ->options(fn (): array => Staff::query()
                            ->active()
                            ->commissioned()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('commission_month')
                        ->label('Commission month')
                        ->default(today()->startOfMonth())
                        ->helperText('This payment will reduce the selected month balance.')
                        ->required(),
                    DatePicker::make('transaction_date')
                        ->label('Payment date')
                        ->default(today())
                        ->helperText('This date is used for bank/cash movement.')
                        ->required(),
                    Select::make('paid_from')
                        ->label('Paid from')
                        ->options(StaffTransaction::paidFromOptions())
                        ->default('cash')
                        ->required(),
                    TextInput::make('amount')
                        ->label('Amount')
                        ->prefix('Rs')
                        ->numeric()
                        ->inputMode('decimal')
                        ->minValue(0.01)
                        ->required(),
                    TextInput::make('description')
                        ->default('Commission payout'),
                ])
                ->action(function (array $data): void {
                    StaffTransactionCreator::create([
                        ...$data,
                        'type' => 'payout',
                    ]);

                    if ($staff = Staff::query()->find($data['staff_id'])) {
                        app(ReportService::class)->generateMonthlyCommission($staff, $data['commission_month']);
                    }
                })
                ->successNotificationTitle('Commission payout recorded'),
        ];
    }
}
