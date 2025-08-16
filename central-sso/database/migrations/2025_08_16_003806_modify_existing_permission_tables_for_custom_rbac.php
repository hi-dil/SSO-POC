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
        // Add missing columns to existing roles table
        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'slug')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
                $table->string('description')->nullable()->after('slug');
                $table->boolean('is_system')->default(false)->after('guard_name');
                $table->json('meta')->nullable()->after('is_system');
                $table->index(['is_system']);
            });
        }

        // Add missing columns to existing permissions table
        if (Schema::hasTable('permissions') && !Schema::hasColumn('permissions', 'slug')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
                $table->string('description')->nullable()->after('slug');
                $table->string('category')->nullable()->after('guard_name');
                $table->boolean('is_system')->default(false)->after('category');
                $table->json('meta')->nullable()->after('is_system');
                $table->index(['category']);
                $table->index(['is_system']);
            });
        }

        // Add tenant_id to model_has_roles table for tenant-specific roles
        if (Schema::hasTable('model_has_roles') && !Schema::hasColumn('model_has_roles', 'tenant_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('model_id');
                $table->index(['model_id', 'tenant_id']);
                $table->index(['role_id', 'tenant_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns
        if (Schema::hasColumn('model_has_roles', 'tenant_id')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasColumn('permissions', 'slug')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn(['slug', 'description', 'category', 'is_system', 'meta']);
            });
        }

        if (Schema::hasColumn('roles', 'slug')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['slug', 'description', 'is_system', 'meta']);
            });
        }
    }
};
