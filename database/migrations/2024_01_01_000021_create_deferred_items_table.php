<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deferred_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Source — where did this deferred item come from?
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('meeting_id')
                  ->nullable()
                  ->constrained('client_meetings')
                  ->nullOnDelete();
            $table->foreignId('scope_item_id')
                  ->nullable()
                  ->constrained('meeting_scope_items')
                  ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Context captured at moment of deferral
            $table->text('client_context')->nullable();
            // What the client said, how they responded, why it matters to them

            $table->text('why_it_matters')->nullable();
            // Your own note: why this is worth keeping and revisiting

            $table->string('client_name')->nullable();
            $table->string('client_quote')->nullable();
            // Exact words from the client that show interest

            // Classification
            $table->enum('deferral_reason', [
                'budget', 'timeline', 'priority',
                'client-not-ready', 'scope-control',
                'awaiting-decision', 'technology', 'personal',
            ])->default('budget');

            $table->enum('opportunity_type', [
                'phase-2', 'upsell', 'upgrade', 'new-project',
                'retainer', 'product-feature', 'personal-goal', 'personal-development', 'none',
            ])->default('none');

            // Client type and resource requirements
            $table->enum('client_type', ['external', 'self'])->default('external');
            $table->jsonb('resource_requirements')->nullable();
            // e.g. { "time": 20, "money": 1500, "capability": "JavaScript", "energy": "medium" }

            $table->boolean('resource_check_done')->default(false);

            // Revenue estimate
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->string('value_notes')->nullable();
            // e.g. "Estimate based on similar project at $8k"

            // Lifecycle
            $table->enum('status', [
                'someday',      // In the Someday/Maybe list — no date yet
                'scheduled',    // Has a revisit date
                'in-review',    // Currently being reconsidered
                'promoted',     // Moved to active Opportunity Pipeline
                'proposed',     // Proposal sent to client
                'won',          // Became a real project
                'lost',         // Revisited and client declined
                'archived',     // No longer relevant
            ])->default('someday');

            $table->date('deferred_on');
            $table->date('revisit_date')->nullable();
            $table->string('revisit_trigger')->nullable();
            // Event-based trigger: "After project X launches"

            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedSmallInteger('review_count')->default(0);

            // AI analysis
            $table->text('ai_opportunity_analysis')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'revisit_date']);
            $table->index('opportunity_type');
        });

        // Now add the FK constraint on meeting_resource_signals.deferred_item_id
        Schema::table('meeting_resource_signals', function (Blueprint $table) {
            $table->foreign('deferred_item_id')
                  ->references('id')
                  ->on('deferred_items')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('meeting_resource_signals', function (Blueprint $table) {
            $table->dropForeign(['deferred_item_id']);
        });

        Schema::dropIfExists('deferred_items');
    }
};
