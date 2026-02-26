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
        // 1. Rename the main table
        Schema::rename('client_meetings', 'meeting_notes');

        // 2. Rename FK columns that explicitly reference "client_meeting"
        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->dropForeign(['client_meeting_id']);
            $table->renameColumn('client_meeting_id', 'meeting_note_id');
        });
        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->foreign('meeting_note_id')->references('id')->on('meeting_notes')->nullOnDelete();
        });

        Schema::table('cost_entries', function (Blueprint $table) {
            $table->dropForeign(['client_meeting_id']);
            $table->renameColumn('client_meeting_id', 'meeting_note_id');
        });
        Schema::table('cost_entries', function (Blueprint $table) {
            $table->foreign('meeting_note_id')->references('id')->on('meeting_notes')->nullOnDelete();
        });

        // 3. Re-point FKs that use generic "meeting_id" column (column stays, FK target changes)
        Schema::table('meeting_done_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
            $table->foreign('meeting_id')->references('id')->on('meeting_notes')->cascadeOnDelete();
        });

        Schema::table('meeting_scope_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
            $table->foreign('meeting_id')->references('id')->on('meeting_notes')->cascadeOnDelete();
        });

        Schema::table('meeting_resource_signals', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
            $table->foreign('meeting_id')->references('id')->on('meeting_notes')->cascadeOnDelete();
        });

        Schema::table('deferred_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
            $table->foreign('meeting_id')->references('id')->on('meeting_notes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-point FKs back to client_meetings
        Schema::table('deferred_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });
        Schema::table('meeting_resource_signals', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });
        Schema::table('meeting_scope_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });
        Schema::table('meeting_done_items', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });

        Schema::table('cost_entries', function (Blueprint $table) {
            $table->dropForeign(['meeting_note_id']);
            $table->renameColumn('meeting_note_id', 'client_meeting_id');
        });
        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->dropForeign(['meeting_note_id']);
            $table->renameColumn('meeting_note_id', 'client_meeting_id');
        });

        // Rename table back
        Schema::rename('meeting_notes', 'client_meetings');

        // Re-add original FKs
        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->foreign('client_meeting_id')->references('id')->on('client_meetings')->nullOnDelete();
        });
        Schema::table('cost_entries', function (Blueprint $table) {
            $table->foreign('client_meeting_id')->references('id')->on('client_meetings')->nullOnDelete();
        });
        Schema::table('meeting_done_items', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('id')->on('client_meetings')->cascadeOnDelete();
        });
        Schema::table('meeting_scope_items', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('id')->on('client_meetings')->cascadeOnDelete();
        });
        Schema::table('meeting_resource_signals', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('id')->on('client_meetings')->cascadeOnDelete();
        });
        Schema::table('deferred_items', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('id')->on('client_meetings')->nullOnDelete();
        });
    }
};
