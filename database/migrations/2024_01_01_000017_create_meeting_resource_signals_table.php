<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_resource_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained('client_meetings')->cascadeOnDelete();
            // FK added without constraint — deferred_items table created in migration 21
            $table->unsignedBigInteger('deferred_item_id')->nullable()->index();

            $table->enum('resource_type', [
                'budget',       // Money / financial resources
                'time',         // Bandwidth / availability
                'technology',   // Platform, tools, infrastructure not yet in place
                'capability',   // Skills or knowledge not yet developed
                'team',         // Headcount or specialist not available
                'readiness',    // Client or self not psychologically/strategically ready
                'dependency',   // Waiting on something else to complete first
            ]);

            $table->text('description');
            $table->string('client_quote')->nullable();

            // When does this constraint lift?
            $table->string('constraint_timeline')->nullable();
            // e.g. "After Q1", "When we hire a developer", "Next year's budget"

            $table->boolean('creates_revisit_opportunity')->default(true);

            $table->timestamps();

            $table->index(['meeting_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_resource_signals');
    }
};
