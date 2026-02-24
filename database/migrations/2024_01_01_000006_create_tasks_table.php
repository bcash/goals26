<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('life_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();

            // Tree structure (from task-decomposition doc)
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('path', 500)->nullable();
            $table->boolean('is_leaf')->default(true);
            $table->enum('decomposition_status', [
                'needs_breakdown',
                'ready',
                'complete',
            ])->default('needs_breakdown');
            $table->boolean('two_minute_check')->default(false);

            // Core task fields
            $table->string('title');
            $table->text('notes')->nullable();
            $table->enum('status', ['todo', 'in-progress', 'done', 'deferred'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('due_date')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->unsignedSmallInteger('time_estimate_minutes')->nullable();
            $table->boolean('is_daily_action')->default(false);

            // Cost tracking (from task-decomposition doc)
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->default(0);
            $table->boolean('billable')->default(false);

            // Quality gate (from task-decomposition doc)
            $table->enum('quality_gate_status', [
                'not_triggered',
                'pending',
                'passed',
                'failed',
            ])->default('not_triggered');

            // Sort order for sibling ordering within a parent
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Deferral fields (from deferral-pipeline doc)
            $table->enum('deferral_reason', [
                'budget',
                'timeline',
                'priority',
                'client-not-ready',
                'scope-control',
                'awaiting-decision',
                'technology',
                'personal',
            ])->nullable();
            $table->text('deferral_note')->nullable();
            $table->date('revisit_date')->nullable();
            $table->string('deferral_trigger')->nullable();
            $table->boolean('has_opportunity')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('parent_id');
            $table->index('path');
            $table->index(['user_id', 'status', 'is_leaf']);
            $table->index(['user_id', 'status', 'revisit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
