<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use App\Services\MonthlyClosingPreview;
use App\Services\ReportService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditMonthlyCommission extends EditRecord
{
    protected static string $resource = MonthlyCommissionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record->fill(array_intersect_key($data, array_flip(['paid_amount', 'paid_from', 'paid_on', 'notes'])));
            $record->save();
            $record = app(ReportService::class)->generateMonthlyCommission(
                $record->staff,
                $record->month,
                paidAmount: (float) $record->paid_amount,
                asOf: $record->period_end,
                paidFrom: $record->paid_from,
            );
            app(MonthlyClosingPreview::class)->clear();

            return $record;
        });
    }

    protected function afterSave(): void
    {
        $this->getRecord()->refresh();
        $this->refreshFormData([
            'cash_collected', 'expense_total', 'net_profit', 'commission_rate', 'commission_amount',
            'carried_forward_from_previous', 'advances_deducted', 'balance_due', 'generated_at', 'paid_on',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
