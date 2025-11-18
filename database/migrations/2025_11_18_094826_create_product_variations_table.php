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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('size', 50)->nullable(); // Product size
            $table->string('color', 50)->nullable(); // Product color
            $table->integer('stock_quantity')->default(0); // Stock for this specific size-color combination
            $table->boolean('in_stock')->default(true); // Availability status
            $table->timestamps();
            
            // Unique constraint: one stock record per product-size-color combination
            $table->unique(['product_id', 'size', 'color'], 'product_variations_unique');
            
            // Index for faster lookups
            $table->index(['product_id', 'size', 'color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
