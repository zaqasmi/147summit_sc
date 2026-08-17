<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_dues', function (Blueprint $table): void {
            $table->decimal('total_discounted', 12, 2)->default(0)->after('total_paid');
        });

        Schema::table('customer_due_payments', function (Blueprint $table): void {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('customer_due_payments', function (Blueprint $table): void {
            $table->dropColumn('discount_amount');
        });

        Schema::table('customer_dues', function (Blueprint $table): void {
            $table->dropColumn('total_discounted');
        });
    }
};
