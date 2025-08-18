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
        Schema::create('user_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // email, phone, mobile, work, home, etc.
            $table->string('label')->nullable(); // Custom label like "Work Phone", "Personal Email"
            $table->string('value'); // The actual contact value
            $table->boolean('is_primary')->default(false); // Mark as primary contact
            $table->boolean('is_public')->default(false); // Whether to show in public profile
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contacts');
    }
};
