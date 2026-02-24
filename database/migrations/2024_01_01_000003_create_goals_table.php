<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('why')->nullable();
            $table->enum('horizon', ['90-day', '1-year', '3-year', 'lifetime'])->default('1-year');
            $table->enum('status', ['active', 'paused', 'achieved', 'abandoned'])->default('active');
            $table->date('target_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};
