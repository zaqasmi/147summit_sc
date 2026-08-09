<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->string('closing_source')->default('system')->after('staff_id');
            $table->decimal('manual_table_1_sale', 12, 2)->default(0)->after('opening_petty_cash');
            $table->decimal('manual_table_2_sale', 12, 2)->default(0)->after('manual_table_1_sale');
            $table->decimal('manual_table_3_sale', 12, 2)->default(0)->after('manual_table_2_sale');
            $table->decimal('manual_table_4_sale', 12, 2)->default(0)->after('manual_table_3_sale');
            $table->decimal('manual_expense_total', 12, 2)->default(0)->after('manual_table_4_sale');
            $table->decimal('staff_paid_amount', 12, 2)->default(0)->after('manual_expense_total');
            $table->decimal('cash_collected_from_counter', 12, 2)->default(0)->after('staff_paid_amount');
        });

        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->foreignId('cash_deposit_id')
                ->nullable()
                ->after('staff_id')
                ->constrained('cash_deposits')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cash_deposit_id');
        });

        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->dropColumn([
                'closing_source',
                'manual_table_1_sale',
                'manual_table_2_sale',
                'manual_table_3_sale',
                'manual_table_4_sale',
                'manual_expense_total',
                'staff_paid_amount',
                'cash_collected_from_counter',
            ]);
        });
    }
};
