<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_due_payments', fn (Blueprint $table) => $table->string('payment_method')->default('cash'));
    }

    public function down(): void
    {
        Schema::table('customer_due_payments', fn (Blueprint $table) => $table->dropColumn('payment_method'));
    }
};
