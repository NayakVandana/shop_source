<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_location_id')->constrained()->onDelete('cascade');
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->integer('estimated_delivery_days')->default(3);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->unique(['product_id', 'delivery_location_id'], 'product_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_delivery_locations');
    }
};