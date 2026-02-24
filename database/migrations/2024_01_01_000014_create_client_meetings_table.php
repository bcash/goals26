<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('client_type', ['external', 'self'])->default('external');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->date('meeting_date');
            $table->enum('meeting_type', [
                'discovery',
                'requirements',
                'check-in',
                'brainstorm',
                'review',
                'planning',
                'retrospective',
                'handoff',
                'other',
            ])->default('other');

            $table->jsonb('attendees')->nullable();
            // Structure: [{ "name": "...", "role": "..." }]

            $table->longText('transcript')->nullable(); // Raw transcript or notes
            $table->string('source')->nullable(); // granola | manual
            $table->string('granola_meeting_id')->nullable()->unique();

            $table->text('summary')->nullable();         // AI or manual summary
            $table->text('decisions')->nullable();        // Key decisions made
            $table->text('action_items')->nullable();     // Immediate follow-ups

            // AI-extracted scope analysis
            $table->text('ai_scope_analysis')->nullable();

            // Transcription / analysis lifecycle
            $table->enum('transcription_status', [
                'pending',
                'processing',
                'complete',
                'failed',
            ])->default('pending');
            $table->timestamp('transcript_received_at')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_meetings');
    }
};
