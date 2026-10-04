<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->foreignId('monthly_commission_id')->nullable()->unique()->constrained('monthly_commissions')->cascadeOnDelete();
        });

        // Add linked records without changing existing payment or bank transaction IDs.
        DB::table('monthly_commissions')->where('paid_amount', '>', 0)->orderBy('id')->chunkById(100, function ($records): void {
            foreach ($records as $record) {
                DB::table('staff_transactions')->insert([
                    'monthly_commission_id' => $record->id,
                    'staff_id' => $record->staff_id,
                    'transaction_date' => $record->paid_on ?: Carbon::parse($record->month)->endOfMonth()->toDateString(),
                    'commission_month' => Carbon::parse($record->month)->startOfMonth()->toDateString(),
                    'type' => 'closing_payment',
                    'paid_from' => $record->paid_from ?: 'unrecorded',
                    'amount' => $record->paid_amount,
                    'description' => $record->notes ?: 'Monthly closing commission payment',
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('staff_transactions')->whereNotNull('monthly_commission_id')->delete();
        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->dropForeign(['monthly_commission_id']);
            $table->dropUnique(['monthly_commission_id']);
            $table->dropColumn('monthly_commission_id');
        });
    }
};
