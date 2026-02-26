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
        Schema::create('server_migration_apps', function (Blueprint $table) {
            $table->id();

            // Cloudways identifiers
            $table->string('cloudways_app_id')->unique();
            $table->string('app_label');
            $table->string('app_cname')->nullable();

            // Domains
            $table->jsonb('domains')->nullable();
            $table->string('primary_domain')->nullable();

            // Classification
            $table->string('category')->default('client');
            $table->boolean('should_migrate')->default(true);

            // Migration state machine
            $table->string('status')->default('pending');

            // Cloudways clone tracking
            $table->string('target_app_id')->nullable();
            $table->string('clone_operation_id')->nullable();
            $table->timestamp('clone_started_at')->nullable();
            $table->timestamp('clone_completed_at')->nullable();

            // DNS tracking
            $table->jsonb('dns_records_updated')->nullable();
            $table->timestamp('dns_switched_at')->nullable();

            // SSL tracking
            $table->boolean('ssl_installed')->default(false);
            $table->timestamp('ssl_installed_at')->nullable();

            // Verification
            $table->boolean('verified')->default(false);
            $table->integer('http_status_code')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Error tracking
            $table->text('last_error')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('should_migrate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_migration_apps');
    }
};
