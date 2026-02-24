<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start_date'); // Always Monday
            $table->text('wins')->nullable();
            $table->text('friction')->nullable();
            $table->jsonb('outcomes_met')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable(); // 1-5
            $table->text('ai_analysis')->nullable();
            $table->text('next_week_focus')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start_date']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reviews');
    }
};
