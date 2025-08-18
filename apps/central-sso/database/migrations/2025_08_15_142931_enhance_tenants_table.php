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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('slug')->unique()->nullable()->after('name');
            $table->string('domain')->nullable()->after('slug');
            $table->boolean('is_active')->default(true)->after('domain');
            $table->integer('max_users')->nullable()->after('is_active');
            $table->text('description')->nullable()->after('max_users');
            $table->string('logo_url')->nullable()->after('description');
            $table->json('settings')->nullable()->after('logo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'slug', 
                'domain',
                'is_active',
                'max_users',
                'description',
                'logo_url',
                'settings'
            ]);
        });
    }
};
