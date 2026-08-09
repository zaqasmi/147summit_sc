<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->json('customer_dues')->nullable()->after('dues_recovered');
        });
    }

    public function down(): void
    {
        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->dropColumn('customer_dues');
        });
    }
};
