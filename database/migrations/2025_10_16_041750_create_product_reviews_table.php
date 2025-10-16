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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->json('images')->nullable(); // Review images
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->json('metadata')->nullable(); // Additional data like size, color, etc.
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'rating']);
            $table->index(['product_id', 'is_approved']);
            $table->index(['user_id', 'product_id']);
            $table->index(['is_verified_purchase']);
            $table->index(['is_featured']);
            $table->index(['reviewed_at']);
            
            // Ensure one review per user per product
            $table->unique(['user_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
