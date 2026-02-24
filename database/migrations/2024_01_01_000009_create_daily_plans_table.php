<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('plan_date');
            $table->string('day_theme')->nullable();
            $table->text('morning_intention')->nullable();

            // Top 3 priorities — nullable FKs to tasks
            $table->foreignId('top_priority_1')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('top_priority_2')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('top_priority_3')->nullable()->constrained('tasks')->nullOnDelete();

            // AI content
            $table->text('ai_morning_prompt')->nullable();
            $table->text('ai_evening_summary')->nullable();

            // Evening ratings (1-5)
            $table->unsignedTinyInteger('energy_rating')->nullable();
            $table->unsignedTinyInteger('focus_rating')->nullable();
            $table->unsignedTinyInteger('progress_rating')->nullable();

            $table->text('evening_reflection')->nullable();
            $table->enum('status', ['draft', 'active', 'reviewed'])->default('draft');
            $table->timestamps();

            // Each user can only have one plan per day
            $table->unique(['user_id', 'plan_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plans');
    }
};
