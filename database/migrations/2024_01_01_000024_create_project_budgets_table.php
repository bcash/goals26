<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

            $table->enum('budget_type', ['fixed', 'hourly', 'retainer'])->default('fixed');
            $table->decimal('budget_total', 10, 2)->nullable(); // Total budget in dollars
            $table->decimal('hourly_rate', 8, 2)->nullable();   // If hourly
            $table->decimal('estimated_hours', 8, 2)->nullable();

            // Rolling calculations — updated by BudgetService
            $table->decimal('actual_spend', 10, 2)->default(0);
            $table->decimal('estimated_remaining', 10, 2)->default(0);
            $table->decimal('burn_rate', 8, 2)->default(0); // Avg cost per day

            $table->unsignedTinyInteger('alert_threshold_percent')->default(80);
            // Trigger alert when actual_spend reaches this % of budget_total

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};
