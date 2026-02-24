<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('interaction_type', [
                'daily-morning',
                'daily-evening',
                'weekly',
                'goal-breakdown',
                'freeform',
            ]);
            $table->jsonb('context_json')->nullable(); // Snapshot of data sent to AI
            $table->text('prompt');
            $table->longText('response')->nullable();
            $table->foreignId('daily_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('tokens_used')->nullable();
            $table->string('model_used')->nullable(); // e.g. "claude-sonnet-4-6"
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_interactions');
    }
};
