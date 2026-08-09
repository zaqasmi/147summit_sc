<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\StaffTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StaffTransactionCreator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): StaffTransaction
    {
        $splitBetweenAllStaff = (bool) ($data['split_between_all_staff'] ?? false);

        unset($data['split_between_all_staff']);

        $transactionDate = Carbon::parse($data['transaction_date'] ?? today())->toDateString();
        $data['transaction_date'] = $transactionDate;
        $data['commission_month'] = filled($data['commission_month'] ?? null)
            ? Carbon::parse($data['commission_month'])->startOfMonth()->toDateString()
            : Carbon::parse($transactionDate)->startOfMonth()->toDateString();

        if (! $splitBetweenAllStaff) {
            return StaffTransaction::query()->create($data);
        }

        $staff = Staff::query()
            ->active()
            ->orderBy('name')
            ->get();

        if ($staff->isEmpty()) {
            throw ValidationException::withMessages([
                'staff_id' => 'No active staff found to split this transaction.',
            ]);
        }

        $totalCents = (int) round(((float) ($data['amount'] ?? 0)) * 100);
        $staffCount = $staff->count();
        $baseCents = intdiv($totalCents, $staffCount);
        $remainderCents = $totalCents % $staffCount;
        $firstRecord = null;

        foreach ($staff->values() as $index => $member) {
            $amountCents = $baseCents + ($index < $remainderCents ? 1 : 0);
            $payload = $data;
            $payload['staff_id'] = $member->id;
            $payload['amount'] = round($amountCents / 100, 2);
            $payload['description'] = filled($data['description'] ?? null)
                ? $data['description']
                : 'Split between all active staff';

            $record = StaffTransaction::query()->create($payload);
            $firstRecord ??= $record;
        }

        return $firstRecord;
    }
}
