<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->enum('entry_type', ['morning', 'evening', 'weekly', 'freeform'])->default('freeform');
            $table->longText('content');
            $table->unsignedTinyInteger('mood')->nullable(); // 1-5
            $table->jsonb('tags')->nullable();
            $table->text('ai_insights')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
