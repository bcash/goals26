<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('life_area_id')->nullable()->constrained('life_areas')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            $table->string('google_event_id')->nullable()->unique();
            $table->string('google_calendar_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('all_day')->default(false);
            $table->jsonb('attendees')->nullable();
            $table->string('organizer_email')->nullable();
            $table->string('status')->default('confirmed');
            $table->string('event_type')->default('meeting');
            $table->string('recurrence_rule')->nullable();
            $table->string('source')->default('manual');
            $table->dateTime('synced_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('start_at');
            $table->index('google_calendar_id');
        });

        // Add CHECK constraints for enum-like columns (PostgreSQL only)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE calendar_events ADD CONSTRAINT calendar_events_status_check CHECK (status IN ('confirmed', 'tentative', 'cancelled'))");
            DB::statement("ALTER TABLE calendar_events ADD CONSTRAINT calendar_events_event_type_check CHECK (event_type IN ('meeting', 'rehearsal', 'personal', 'focus', 'other'))");
            DB::statement("ALTER TABLE calendar_events ADD CONSTRAINT calendar_events_source_check CHECK (source IN ('google', 'manual'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
