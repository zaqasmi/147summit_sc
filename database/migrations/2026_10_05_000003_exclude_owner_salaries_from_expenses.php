<?php

use App\Services\ReportService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $expenses = DB::table('expenses')->whereNotNull('staff_transaction_id')->get();
            if ($expenses->isEmpty()) {
                return;
            }

            // Delete only the generated expense mirrors, keeping salary and bank IDs intact.
            DB::table('expenses')->whereNotNull('staff_transaction_id')->delete();
            $reports = app(ReportService::class);
            $affectedStaff = [];
            foreach ($expenses->groupBy(fn ($expense) => Carbon::parse($expense->expense_date)->startOfMonth()->toDateString()) as $month => $salaryExpenses) {
                $commissions = DB::table('monthly_commissions')->whereDate('month', $month)->get();
                $weightTotal = (float) $commissions->sum('commission_rate');
                foreach ($commissions->groupBy('period_end') as $periodEnd => $records) {
                    $periodEnd = $periodEnd ?: Carbon::parse($month)->endOfMonth()->toDateString();
                    if (! $salaryExpenses->contains(fn ($expense) => $expense->expense_date <= $periodEnd)) {
                        continue;
                    }
                    $report = $reports->monthly($month, asOf: $periodEnd);
                    $pool = (float) $report['commission_pool'];
                    foreach ($records as $record) {
                        // Preserve the saved staff distribution, including now-inactive staff.
                        $share = $weightTotal > 0 ? (float) $record->commission_rate / $weightTotal : 1 / max(1, $commissions->count());
                        DB::table('monthly_commissions')->where('id', $record->id)->update([
                            'cash_collected' => $report['cash_collected'],
                            'expense_total' => $report['expense_total'],
                            'net_profit' => $report['net_profit'],
                            'commission_rate' => round((float) $report['commission_rate'] * $share, 2),
                            'commission_amount' => round($pool * $share, 2),
                        ]);
                        $affectedStaff[$record->staff_id] = true;
                    }
                }
            }

            // Rebuild carries without modifying payment records or bank ledgers.
            foreach (array_keys($affectedStaff) as $staffId) {
                $history = DB::table('monthly_commissions')->where('staff_id', $staffId)->orderBy('month')->get();
                $carry = (float) ($history->first()->carried_forward_from_previous ?? 0);
                foreach ($history as $record) {
                    $balance = round($carry + (float) $record->commission_amount - (float) $record->advances_deducted - (float) $record->paid_amount, 2);
                    DB::table('monthly_commissions')->where('id', $record->id)->update([
                        'carried_forward_from_previous' => $carry,
                        'balance_due' => $balance,
                    ]);
                    $carry = $balance;
                }
            }
        });
    }

    public function down(): void
    {
        // Owner-paid salaries must remain excluded from expenses on rollback.
    }
};
