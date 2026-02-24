<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_pipeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deferred_item_id')
                  ->constrained('deferred_items')
                  ->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            // Linked to the resulting project if won

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('client_name');
            $table->string('client_email')->nullable();

            $table->enum('stage', [
                'identified',   // Captured from deferral
                'qualifying',   // Assessing fit and timing
                'nurturing',    // Staying in touch until the time is right
                'proposing',    // Proposal being prepared or sent
                'negotiating',  // In active discussion
                'closed-won',   // Became a real engagement
                'closed-lost',  // Permanently declined
            ])->default('identified');

            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->decimal('actual_value', 10, 2)->nullable();
            // Filled in when closed-won

            $table->unsignedTinyInteger('probability_percent')->default(20);
            // Weighted pipeline value = estimated_value x probability_percent / 100

            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();

            $table->text('next_action')->nullable();
            $table->date('next_action_date')->nullable();

            $table->text('notes')->nullable();
            $table->text('lost_reason')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'stage']);
            $table->index(['user_id', 'next_action_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_pipeline');
    }
};
