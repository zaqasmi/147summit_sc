<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('staff_transaction_id')->nullable()->unique()->constrained('staff_transactions')->cascadeOnDelete();
        });
        DB::table('staff_transactions')->where('type', 'salary')->orderBy('id')->chunkById(100, function ($records): void {
            foreach ($records as $record) {
                DB::table('expenses')->insert([
                    'staff_transaction_id' => $record->id, 'staff_id' => $record->staff_id,
                    'expense_date' => $record->transaction_date, 'category' => 'Salary',
                    'description' => $record->description ?: 'Staff salary payment',
                    'amount' => $record->amount, 'paid_from' => $record->paid_from,
                    'created_at' => $record->created_at, 'updated_at' => $record->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('expenses')->whereNotNull('staff_transaction_id')->delete();
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['staff_transaction_id']);
            $table->dropUnique(['staff_transaction_id']);
            $table->dropColumn('staff_transaction_id');
        });
    }
};
