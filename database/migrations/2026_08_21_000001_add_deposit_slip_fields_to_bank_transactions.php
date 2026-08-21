<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table): void {
            $table->string('deposit_slip_number')->nullable()->after('amount');
            $table->date('deposit_slip_date')->nullable()->after('deposit_slip_number');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table): void {
            $table->dropColumn(['deposit_slip_number', 'deposit_slip_date']);
        });
    }
};
