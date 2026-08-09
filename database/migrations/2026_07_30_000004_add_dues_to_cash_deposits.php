<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->decimal('dues_added', 12, 2)->default(0)->after('manual_expense_total');
            $table->decimal('dues_recovered', 12, 2)->default(0)->after('dues_added');
        });
    }

    public function down(): void
    {
        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->dropColumn([
                'dues_added',
                'dues_recovered',
            ]);
        });
    }
};
