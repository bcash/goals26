<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_done_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->constrained('client_meetings')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // What did completing this achieve?
            $table->text('outcome')->nullable();
            $table->string('outcome_metric')->nullable();
            // e.g. "Cart abandonment down 18%", "Page speed improved 40%"

            // Client's exact words about the completed work
            $table->text('client_reaction')->nullable();
            $table->string('client_quote')->nullable();

            $table->decimal('value_delivered', 10, 2)->nullable();
            // Estimated $ value of the outcome to the client

            $table->boolean('save_as_testimonial')->default(false);
            $table->boolean('save_for_portfolio')->default(false);
            $table->boolean('save_for_case_study')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_done_items');
    }
};
