<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('freescout_conversation_id')->unique();
            $table->integer('freescout_mailbox_id')->nullable();
            $table->foreignId('email_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->string('preview', 500)->nullable();
            $table->string('status')->default('active');
            $table->string('type')->default('email');
            $table->string('assigned_to_name')->nullable();
            $table->string('assigned_to_email')->nullable();
            $table->jsonb('tags')->nullable();
            $table->integer('thread_count')->default(0);
            $table->string('importance')->default('normal');
            $table->string('category')->default('general');
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('ai_sentiment')->nullable();
            $table->integer('ai_priority_score')->nullable();
            $table->string('analysis_status')->default('pending');
            $table->boolean('needs_review')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_conversations');
    }
};
