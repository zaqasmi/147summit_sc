<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->string('paid_from')->default('cash')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('staff_transactions', function (Blueprint $table): void {
            $table->dropColumn('paid_from');
        });
    }
};
