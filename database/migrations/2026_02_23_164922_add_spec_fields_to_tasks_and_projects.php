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
            $table->text('acceptance_criteria')->nullable()->after('context');
            $table->text('technical_requirements')->nullable()->after('acceptance_criteria');
            $table->text('dependencies_description')->nullable()->after('technical_requirements');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->text('tech_stack')->nullable()->after('description');
            $table->text('architecture_notes')->nullable()->after('tech_stack');
            $table->text('export_template')->nullable()->after('architecture_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['acceptance_criteria', 'technical_requirements', 'dependencies_description']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tech_stack', 'architecture_notes', 'export_template']);
        });
    }
};
