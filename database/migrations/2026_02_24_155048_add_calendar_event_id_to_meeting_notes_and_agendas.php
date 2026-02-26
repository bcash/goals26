<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_notes', function (Blueprint $table) {
            $table->foreignId('calendar_event_id')->nullable()
                ->constrained('calendar_events')->nullOnDelete();
        });

        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->foreignId('calendar_event_id')->nullable()
                ->constrained('calendar_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('meeting_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('calendar_event_id');
        });

        Schema::table('meeting_agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('calendar_event_id');
        });
    }
};
