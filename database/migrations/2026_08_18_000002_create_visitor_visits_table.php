<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_visits', function (Blueprint $table): void {
            $table->id();
            $table->string('route_name')->nullable()->index();
            $table->string('path')->index();
            $table->string('visitor_hash')->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->dateTime('visited_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_visits');
    }
};
