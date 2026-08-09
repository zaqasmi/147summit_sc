<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capital_liabilities', function (Blueprint $table): void {
            $table->id();
            $table->date('start_date')->index();
            $table->string('title');
            $table->string('source_type')->default('friend');
            $table->string('lender_name')->nullable();
            $table->string('category')->default('Other');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('installment_amount', 12, 2)->default(0);
            $table->string('installment_frequency')->default('monthly');
            $table->date('due_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('capital_liability_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('capital_liability_id')->constrained('capital_liabilities')->cascadeOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('paid_from')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capital_liability_payments');
        Schema::dropIfExists('capital_liabilities');
    }
};
