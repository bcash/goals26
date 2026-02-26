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
        Schema::table('email_contacts', function (Blueprint $table) {
            // Add VPO contact ID for linking to VPO contact records
            $table->string('vpo_contact_id', 50)->nullable()->after('vpo_account_id')->index();

            // Fix vpo_account_id type from bigint to string(50) to match other models
            $table->string('vpo_account_id', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_contacts', function (Blueprint $table) {
            $table->dropColumn('vpo_contact_id');
            $table->unsignedBigInteger('vpo_account_id')->nullable()->change();
        });
    }
};
