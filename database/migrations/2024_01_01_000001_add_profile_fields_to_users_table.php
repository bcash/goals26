<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('email');
            $table->enum('subscription_status', [
                'trial',
                'active',
                'cancelled',
                'expired',
            ])->default('trial')->after('timezone');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('stripe_customer_id')->nullable()->after('subscription_ends_at');
            $table->boolean('onboarding_complete')->default(false)->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'subscription_status',
                'trial_ends_at',
                'subscription_ends_at',
                'stripe_customer_id',
                'onboarding_complete',
            ]);
        });
    }
};
