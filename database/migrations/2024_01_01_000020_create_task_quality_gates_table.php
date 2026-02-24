<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_quality_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();

            $table->timestamp('triggered_at');      // When the gate auto-fired
            $table->timestamp('reviewed_at')->nullable(); // When the user completed the review

            $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');

            // AI-generated checklist of review questions for this task type
            $table->jsonb('checklist')->nullable();
            // Structure: [{ "question": "...", "answer": null, "passed": null }]

            $table->text('reviewer_notes')->nullable(); // Free-form notes during review
            $table->text('failure_reason')->nullable(); // If failed — what needs rework?

            $table->unsignedSmallInteger('children_completed')->default(0);
            $table->unsignedSmallInteger('children_total')->default(0);

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_quality_gates');
    }
};
