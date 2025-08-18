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
        Schema::create('user_social_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('platform'); // facebook, twitter, linkedin, instagram, github, etc.
            $table->string('username')->nullable(); // Username/handle
            $table->string('url'); // Full URL to profile
            $table->string('display_name')->nullable(); // How to display this link
            $table->boolean('is_public')->default(true); // Whether to show in public profile
            $table->integer('order')->default(0); // Display order
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            $table->index(['user_id', 'platform']);
            $table->index(['user_id', 'is_public']);
            $table->index(['user_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_media');
    }
};
