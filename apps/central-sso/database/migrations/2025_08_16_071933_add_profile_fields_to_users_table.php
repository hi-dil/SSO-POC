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
        Schema::table('users', function (Blueprint $table) {
            // Personal Information
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('nationality')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            
            // Address Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // Work Information
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('employee_id')->nullable();
            $table->date('hire_date')->nullable();
            
            // Preferences
            $table->string('timezone')->default('UTC');
            $table->string('language')->default('en');
            $table->json('notification_preferences')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'date_of_birth', 'gender', 'nationality', 'bio', 'avatar_url',
                'address_line_1', 'address_line_2', 'city', 'state_province', 'postal_code', 'country',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
                'job_title', 'department', 'employee_id', 'hire_date',
                'timezone', 'language', 'notification_preferences'
            ]);
        });
    }
};
