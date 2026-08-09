<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_capitals', function (Blueprint $table): void {
            $table->string('source_type')->nullable()->after('notes');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->unique(['source_type', 'source_id']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('owner_capitals', function (Blueprint $table): void {
            $table->dropUnique(['source_type', 'source_id']);
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
