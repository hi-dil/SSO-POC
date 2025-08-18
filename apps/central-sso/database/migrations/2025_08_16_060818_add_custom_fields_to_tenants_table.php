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
            $table->string('plan')->nullable()->after('data');
            $table->string('industry')->nullable()->after('plan');
            $table->string('region')->nullable()->after('industry');
            $table->integer('employee_count')->nullable()->after('region');
            $table->integer('created_year')->nullable()->after('employee_count');
            $table->json('features')->nullable()->after('created_year');
            $table->string('billing_status')->nullable()->after('features');
            $table->string('billing_cycle')->nullable()->after('billing_status');
            $table->date('trial_ends_at')->nullable()->after('billing_cycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'industry', 
                'region',
                'employee_count',
                'created_year',
                'features',
                'billing_status',
                'billing_cycle',
                'trial_ends_at'
            ]);
        });
    }
};
