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
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            // Performance indexes for audit log queries
            $table->index(['causer_id', 'causer_type', 'created_at'], 'activity_log_causer_created_at_index');
            $table->index(['subject_id', 'subject_type', 'created_at'], 'activity_log_subject_created_at_index');
            $table->index(['log_name', 'created_at'], 'activity_log_log_name_created_at_index');
            $table->index(['created_at'], 'activity_log_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $table) {
            $table->dropIndex('activity_log_causer_created_at_index');
            $table->dropIndex('activity_log_subject_created_at_index');
            $table->dropIndex('activity_log_log_name_created_at_index');
            $table->dropIndex('activity_log_created_at_index');
        });
    }
};
