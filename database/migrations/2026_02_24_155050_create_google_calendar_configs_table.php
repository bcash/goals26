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
        Schema::create('google_calendar_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('google_calendar_id');
            $table->string('calendar_name');
            $table->foreignId('life_area_id')->nullable()->constrained('life_areas')->nullOnDelete();
            $table->boolean('sync_enabled')->default(true);
            $table->boolean('only_with_attendees')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'google_calendar_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_calendar_configs');
    }
};
