<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deferral_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deferred_item_id')
                  ->constrained('deferred_items')
                  ->cascadeOnDelete();

            $table->date('reviewed_on');

            $table->enum('outcome', [
                'keep-someday',   // Still valid, no date yet
                'reschedule',     // Set a new revisit date
                'promote',        // Move to Opportunity Pipeline
                'propose',        // Ready to send a proposal
                'archive',        // No longer relevant
            ]);

            $table->date('next_revisit_date')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('context_update')->nullable();
            // Any new information that emerged since last review

            $table->timestamps();

            $table->index(['deferred_item_id', 'reviewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deferral_reviews');
    }
};
