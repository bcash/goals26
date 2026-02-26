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
        Schema::table('deferred_items', function (Blueprint $table) {
            $table->foreignId('email_conversation_id')->nullable()->after('meeting_id')
                ->constrained('email_conversations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deferred_items', function (Blueprint $table) {
            $table->dropForeign(['email_conversation_id']);
            $table->dropColumn('email_conversation_id');
        });
    }
};
