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
        Schema::table('tasks', function (Blueprint $table) {
            // Implementation plan — steps, approach, architecture decisions.
            // Retained across AI sessions so agents can pick up where they left off.
            $table->text('plan')->nullable()->after('notes');

            // Key context — files, specs, requirements, decisions, and constraints.
            // Gives AI agents the "working memory" for this task.
            $table->text('context')->nullable()->after('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['plan', 'context']);
        });
    }
};
