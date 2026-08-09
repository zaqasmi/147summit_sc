<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snooker_table_id')->constrained('snooker_tables')->restrictOnDelete();
            $table->string('game_type')->default('one_to_one')->index();
            $table->string('status')->default('active')->index();
            $table->dateTime('started_at')->index();
            $table->dateTime('ended_at')->nullable()->index();
            $table->timestamp('checked_out_at')->nullable()->index();
            $table->unsignedInteger('frames_played')->default(1);
            $table->decimal('frame_fee', 10, 2)->default(100);
            $table->decimal('hourly_rate', 10, 2)->default(10);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('game_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('player_name_snapshot')->nullable();
            $table->string('team')->default('solo')->index();
            $table->boolean('is_loser')->default(false)->index();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('add_on_amount', 12, 2)->default(0);
            $table->decimal('total_due', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('game_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('add_on_item_id')->nullable()->constrained('add_on_items')->nullOnDelete();
            $table->string('item_name');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('charged_to')->default('losers');
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->nullable()->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('game_participant_id')->nullable()->constrained('game_participants')->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('collected_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('payment_date')->index();
            $table->string('payment_method')->default('cash');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('game_add_ons');
        Schema::dropIfExists('game_participants');
        Schema::dropIfExists('game_sessions');
    }
};
