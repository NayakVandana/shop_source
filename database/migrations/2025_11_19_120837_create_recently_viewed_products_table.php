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
        Schema::create('recently_viewed_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 100)->nullable()->index(); // For guest users
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'viewed_at']);
            
            // Unique constraints - handle NULLs properly
            // For authenticated users: one entry per user per product
            $table->unique(['user_id', 'product_id'], 'recently_viewed_user_product_unique');
            // For guest users: one entry per session per product  
            $table->unique(['session_id', 'product_id'], 'recently_viewed_session_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_products');
    }
};
