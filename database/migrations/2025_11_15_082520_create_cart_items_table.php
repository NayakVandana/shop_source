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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('size', 50)->nullable(); // Product size (S, M, L, etc.)
            $table->string('color', 100)->nullable(); // Product color (Red, Blue, etc.)
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2); // Price at time of adding to cart
            $table->decimal('discount_amount', 10, 2)->default(0); // Discount applied
            $table->timestamps();
            
            // Unique constraint: same product with same size and color in same cart
            $table->unique(['cart_id', 'product_id', 'size', 'color'], 'cart_items_cart_product_size_color_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
