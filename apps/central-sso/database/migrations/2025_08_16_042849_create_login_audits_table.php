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
        Schema::create('login_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tenant_id')->nullable(); // Which tenant they accessed
            $table->enum('login_method', ['sso', 'direct', 'api'])->default('direct');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->integer('session_duration')->nullable(); // In seconds
            $table->boolean('is_successful')->default(true);
            $table->text('failure_reason')->nullable(); // For failed login attempts
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'login_at']);
            $table->index(['tenant_id', 'login_at']);
            $table->index(['login_at']);
            $table->index(['is_successful']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audits');
    }
};
