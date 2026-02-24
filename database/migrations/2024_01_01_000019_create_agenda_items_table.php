<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained('meeting_agendas')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('item_type', [
                'topic',
                'action-followup',
                'deferred-review',
                'decision',
                'new-business',
                'budget-check',
            ])->default('topic');

            // Source linking — where did this agenda item come from?
            $table->string('source_type')->nullable();
            // 'task' | 'deferred_item' | 'scope_item' | 'manual'
            $table->unsignedBigInteger('source_id')->nullable();

            $table->unsignedSmallInteger('time_allocation_minutes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->enum('status', [
                'pending', 'discussed', 'deferred', 'resolved', 'skipped',
            ])->default('pending');

            $table->text('outcome_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
