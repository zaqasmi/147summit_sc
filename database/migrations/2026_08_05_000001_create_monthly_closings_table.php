<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_closings', function (Blueprint $table): void {
            $table->id();
            $table->date('month')->unique();
            $table->string('status')->default('draft')->index();
            $table->decimal('rent_total', 12, 2)->default(0);
            $table->decimal('rent_paid_amount', 12, 2)->default(0);
            $table->string('rent_paid_from')->default('bank');
            $table->decimal('construction_deduction_amount', 12, 2)->default(0);
            $table->decimal('construction_received_amount', 12, 2)->default(0);
            $table->string('construction_account_name')->nullable();
            $table->decimal('sales_total', 12, 2)->default(0);
            $table->decimal('cash_collected', 12, 2)->default(0);
            $table->decimal('expense_total', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('staff_paid_total', 12, 2)->default(0);
            $table->decimal('liabilities_paid_amount', 12, 2)->default(0);
            $table->boolean('liabilities_verified')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $rentExpenseIds = DB::table('expenses')
            ->where('category', 'Rent')
            ->orWhere('description', 'like', '%rent%')
            ->pluck('id');

        if ($rentExpenseIds->isNotEmpty()) {
            DB::table('bank_transactions')
                ->where('source_type', 'expense')
                ->whereIn('source_id', $rentExpenseIds)
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_closings');
    }
};
