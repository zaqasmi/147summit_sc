<?php

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Models\Staff;
use App\Services\MonthlyClosingPreview;
use App\Services\ReportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait SavesCommissionPayments
{
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $payments = $data['commission_paid_overrides'] ?? [];
            $sources = $data['commission_payment_sources'] ?? [];
            unset($data['commission_paid_overrides'], $data['commission_payment_sources']);
            $data['month'] = Carbon::parse($data['month'])->startOfMonth()->toDateString();
            $record = parent::handleRecordCreation($data);
            $this->saveCommissionPayments($record, $payments, $sources);

            return $record;
        });
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $payments = $data['commission_paid_overrides'] ?? [];
            $sources = $data['commission_payment_sources'] ?? [];
            unset($data['commission_paid_overrides'], $data['commission_payment_sources']);
            $data['month'] = Carbon::parse($data['month'])->startOfMonth()->toDateString();
            $record = parent::handleRecordUpdate($record, $data);
            $this->saveCommissionPayments($record, $payments, $sources);

            return $record;
        });
    }

    private function saveCommissionPayments(Model $record, array $payments, array $sources): void
    {
        $service = app(ReportService::class);
        $report = $service->monthly($record->month);
        foreach (Staff::query()->active()->commissioned()->get() as $staff) {
            $service->generateMonthlyCommission(
                $staff,
                $record->month,
                paidAmount: isset($payments[$staff->id]) ? round((float) $payments[$staff->id], 2) : null,
                paidFrom: $sources[$staff->id] ?? null,
                report: $report,
            );
        }
        app(MonthlyClosingPreview::class)->clear();
    }
}
