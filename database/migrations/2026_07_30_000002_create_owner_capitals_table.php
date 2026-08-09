<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_capitals', function (Blueprint $table): void {
            $table->id();
            $table->date('entry_date')->index();
            $table->string('type')->default('investment');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_capitals');
    }
};
