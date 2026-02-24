<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_scope_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')
                  ->constrained('client_meetings')
                  ->cascadeOnDelete();

            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            // Linked to a task if this scope item has been actioned

            $table->text('description');

            $table->enum('type', [
                'in-scope',     // Client confirmed this is included
                'out-of-scope', // Client confirmed this is excluded
                'deferred',     // Acknowledged but pushed to a future phase
                'assumption',   // Team assumed this — not explicitly confirmed
                'risk',         // Identified risk to scope or budget
            ]);

            $table->boolean('confirmed_with_client')->default(false);
            $table->text('client_quote')->nullable(); // Direct quote from transcript
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('meeting_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_scope_items');
    }
};
