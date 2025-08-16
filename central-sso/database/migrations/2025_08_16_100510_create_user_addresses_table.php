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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // home, work, billing, shipping, etc.
            $table->string('label')->nullable(); // Custom label
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country');
            $table->boolean('is_primary')->default(false); // Mark as primary address
            $table->boolean('is_public')->default(false); // Whether to show in public profile
            $table->text('notes')->nullable(); // Additional notes
            $table->decimal('latitude', 10, 8)->nullable(); // For mapping
            $table->decimal('longitude', 11, 8)->nullable(); // For mapping
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
        Schema::dropIfExists('user_addresses');
    }
};
