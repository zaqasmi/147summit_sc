<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Filament\Resources\MonthlyCommissions\Widgets\StaffCommissionOverallSummary;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Services\ReportService;
use App\Support\StaffTransactionCreator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class ListMonthlyCommissions extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = MonthlyCommissionResource::class;

    public function mount(): void
    {
        parent::mount();

        app(ReportService::class)->generateMonthlyCommissions(today()->startOfMonth(), today());
    }

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
                    DatePicker::make('period_end')
                        ->label('Generate through date')
                        ->default(today())
                        ->helperText('For the current month, use today to see commission earned and paid till now. For old months, the system will clamp this to that month.')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $records = app(ReportService::class)->generateMonthlyCommissions($data['month'], $data['period_end']);

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

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            StaffCommissionOverallSummary::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
