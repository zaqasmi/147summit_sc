<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('cash_deposits')
            || ! Schema::hasTable('staff_transactions')
            || ! Schema::hasColumn('cash_deposits', 'staff_paid_amount')
            || ! Schema::hasColumn('staff_transactions', 'cash_deposit_id')
        ) {
            return;
        }

        DB::transaction(function (): void {
            $deposits = DB::table('cash_deposits')
                ->where('staff_paid_amount', '>', 0)
                ->orderBy('id')
                ->get();

            foreach ($deposits as $deposit) {
                $amount = round((float) $deposit->staff_paid_amount, 2);

                if ($amount <= 0) {
                    continue;
                }

                $linkedAdvances = DB::table('staff_transactions')
                    ->where('cash_deposit_id', $deposit->id)
                    ->where('type', 'advance')
                    ->count();

                if ($linkedAdvances > 0) {
                    DB::table('staff_transactions')
                        ->where('cash_deposit_id', $deposit->id)
                        ->where('type', 'advance')
                        ->update([
                            'cash_deposit_id' => null,
                            'updated_at' => now(),
                        ]);
                } else {
                    $this->createMissingAdvanceTransactions($deposit, $amount);
                }

                $updates = [
                    'amount_collected_from_staff' => round((float) $deposit->amount_collected_from_staff + $amount, 2),
                    'staff_paid_amount' => 0,
                    'staff_id' => null,
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('cash_deposits', 'staff_paid_to_all')) {
                    $updates['staff_paid_to_all'] = false;
                }

                DB::table('cash_deposits')
                    ->where('id', $deposit->id)
                    ->update($updates);
            }
        });
    }

    public function down(): void
    {
        //
    }

    private function createMissingAdvanceTransactions(object $deposit, float $amount): void
    {
        if (property_exists($deposit, 'staff_paid_to_all') && (bool) $deposit->staff_paid_to_all) {
            $staff = DB::table('staff')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id']);

            if ($staff->isEmpty()) {
                return;
            }

            $totalCents = (int) round($amount * 100);
            $staffCount = $staff->count();
            $baseCents = intdiv($totalCents, $staffCount);
            $remainderCents = $totalCents % $staffCount;

            foreach ($staff->values() as $index => $member) {
                $amountCents = $baseCents + ($index < $remainderCents ? 1 : 0);

                $this->insertAdvanceTransaction(
                    (int) $member->id,
                    $deposit,
                    round($amountCents / 100, 2),
                    'Daily closing staff advance - migrated',
                );
            }

            return;
        }

        if (! $deposit->staff_id) {
            return;
        }

        $this->insertAdvanceTransaction(
            (int) $deposit->staff_id,
            $deposit,
            $amount,
            'Daily closing staff advance - migrated',
        );
    }

    private function insertAdvanceTransaction(int $staffId, object $deposit, float $amount, string $description): void
    {
        $date = Carbon::parse($deposit->deposit_date);

        DB::table('staff_transactions')->insert([
            'staff_id' => $staffId,
            'cash_deposit_id' => null,
            'transaction_date' => $date->toDateString(),
            'commission_month' => $date->copy()->startOfMonth()->toDateString(),
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => round($amount, 2),
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
