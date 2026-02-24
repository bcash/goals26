<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekdays', 'weekly', 'custom'])->default('daily');
            $table->jsonb('target_days')->nullable(); // [0,1,2,3,4,5,6] — 0=Sun
            $table->enum('time_of_day', ['morning', 'afternoon', 'evening', 'anytime'])->default('anytime');
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->unsignedSmallInteger('streak_current')->default(0);
            $table->unsignedSmallInteger('streak_best')->default(0);
            $table->date('started_at');
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habits');
    }
};
