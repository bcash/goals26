<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description')->nullable();
            $table->decimal('hours', 6, 2);
            $table->date('logged_date');
            $table->boolean('billable')->default(true);
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Override project rate if needed
            $table->decimal('cost', 8, 2)->default(0);        // hours x rate

            $table->timestamps();

            $table->index('user_id');
            $table->index(['project_id', 'logged_date']);
            $table->index(['task_id', 'logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
