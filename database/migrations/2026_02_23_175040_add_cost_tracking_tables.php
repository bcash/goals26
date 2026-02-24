<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('budget_cents')->nullable()->after('color_hex');
            $table->string('budget_currency', 3)->default('USD')->after('budget_cents');
        });

        Schema::create('cost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_meeting_id')->nullable()
                ->constrained('client_meetings')->nullOnDelete();
            $table->string('description');
            $table->string('category');
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->integer('duration_minutes')->nullable();
            $table->boolean('billable')->default(true);
            $table->date('logged_date');
            $table->timestamps();

            $table->index('project_id');
            $table->index('task_id');
            $table->index('logged_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_entries');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['budget_cents', 'budget_currency']);
        });
    }
};
