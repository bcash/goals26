<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_meeting_id')->nullable()
                  ->constrained('client_meetings')->nullOnDelete();

            $table->string('title');
            $table->enum('client_type', ['external', 'self'])->default('external');
            $table->string('client_name')->nullable();
            $table->dateTime('scheduled_for')->nullable();
            $table->text('purpose')->nullable();
            $table->jsonb('desired_outcomes')->nullable();
            $table->enum('status', [
                'draft', 'ready', 'in-progress', 'complete', 'cancelled',
            ])->default('draft');
            $table->jsonb('ai_suggested_topics')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_agendas');
    }
};
