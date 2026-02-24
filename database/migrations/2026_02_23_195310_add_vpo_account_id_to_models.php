<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add vpo_account_id (soft reference) to client-facing models.
     *
     * No foreign key constraint — VPO is an external database.
     */
    public function up(): void
    {
        $tables = [
            'projects',
            'client_meetings',
            'opportunity_pipeline',
            'deferred_items',
            'meeting_agendas',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('vpo_account_id', 50)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'projects',
            'client_meetings',
            'opportunity_pipeline',
            'deferred_items',
            'meeting_agendas',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('vpo_account_id');
            });
        }
    }
};
