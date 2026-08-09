<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('knockout');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->unsignedInteger('max_players')->nullable();
            $table->longText('rules')->nullable();
            $table->string('match_format')->default('best_of_5');
            $table->string('status')->default('upcoming');
            $table->dateTime('draw_generated_at')->nullable();
            $table->text('prize_notes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
