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
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_conversation_id')->constrained()->cascadeOnDelete();
            $table->integer('freescout_thread_id')->unique();
            $table->string('type');
            $table->text('body');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->jsonb('to_emails')->nullable();
            $table->jsonb('cc_emails')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachment_count')->default(0);
            $table->integer('ai_quality_score')->nullable();
            $table->text('ai_quality_notes')->nullable();
            $table->timestamp('message_at');
            $table->timestamps();

            $table->index('type');
            $table->index('message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};
