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
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tenant_id')->nullable(); // Current tenant context
            $table->string('session_id')->unique(); // Laravel session ID
            $table->enum('login_method', ['sso', 'direct', 'api'])->default('direct');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity');
            $table->timestamp('expires_at')->nullable();
            $table->json('activity_data')->nullable(); // Store additional activity info
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'last_activity']);
            $table->index(['tenant_id', 'last_activity']);
            $table->index(['session_id']);
            $table->index(['last_activity']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_sessions');
    }
};
