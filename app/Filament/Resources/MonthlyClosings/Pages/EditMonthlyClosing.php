<?php

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Filament\Resources\MonthlyClosings\MonthlyClosingResource;
use App\Filament\Resources\MonthlyClosings\Schemas\MonthlyClosingForm;
use App\Models\MonthlyClosing;
use App\Models\Staff;
use App\Services\ReportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EditMonthlyClosing extends EditRecord
{
    use SavesCommissionPayments;

    protected static string $resource = MonthlyClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('commissionPayments')
                ->label('Commission payments')
                ->icon('heroicon-o-banknotes')
                ->modalHeading(fn (MonthlyClosing $record): string => 'Commission payments for '.($record->month?->format('F Y') ?? 'selected month'))
                ->modalSubmitActionLabel('Update payments')
                ->form(fn (MonthlyClosing $record): array => $this->commissionPaymentForm($record))
                ->fillForm(fn (MonthlyClosing $record): array => $this->commissionPaymentDefaults($record))
                ->action(function (MonthlyClosing $record, array $data): void {
                    $month = Carbon::parse($record->month)->startOfMonth();
                    $reportService = app(ReportService::class);

                    DB::transaction(fn () => Staff::query()
                        ->active()
                        ->commissioned()
                        ->orderBy('name')
                        ->get()
                        ->each(function (Staff $staff) use ($data, $month, $reportService): void {
                            $reportService->generateMonthlyCommission(
                                $staff,
                                $month,
                                paidAmount: max(0, round((float) ($data['staff_'.$staff->id] ?? 0), 2)),
                            );
                        }));

                    $this->refreshFormData(['commission_paid_overrides']);
                    $this->data['commission_paid_overrides'] = MonthlyClosingForm::paymentDefaults($month);
                })
                ->successNotificationTitle('Commission payments updated'),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    private function commissionPaymentForm(MonthlyClosing $record): array
    {
        return collect(app(ReportService::class)->monthly($record->month)['staff_shares'])
            ->map(function (array $row): TextInput {
                $staff = $row['staff'];
                $alreadyPaid = (float) $row['advance_paid'] + (float) $row['payout_paid'];

                return TextInput::make('staff_'.$staff->id)
                    ->label($staff->name.' — total paid at monthly closing')
                    ->prefix('Rs')
                    ->numeric()
                    ->inputMode('decimal')
                    ->minValue(0)
                    ->required()
                    ->live(onBlur: true)
                    ->helperText(fn (Get $get): string => 'Commission '.$this->money($row['monthly_commission_to_be_paid'])
                        .' | Previous balance '.$this->money($row['previous_balance'])
                        .' | Advance paid '.$this->money($row['advance_paid'])
                        .' | Other payouts '.$this->money($row['payout_paid'])
                        .' | Remaining due '.$this->money(max(0, (float) $row['total_payable'] - $alreadyPaid - (float) $get('staff_'.$staff->id)))
                        .' | Advance carried forward '.$this->money(max(0, $alreadyPaid + (float) $get('staff_'.$staff->id) - (float) $row['total_payable']))
                    );
            })
            ->all();
    }

    /**
     * @return array<string, float>
     */
    private function commissionPaymentDefaults(MonthlyClosing $record): array
    {
        return collect(app(ReportService::class)->monthly($record->month)['staff_shares'])
            ->mapWithKeys(fn (array $row): array => [
                'staff_'.$row['staff']->id => (float) $row['paid_amount'],
            ])
            ->all();
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
