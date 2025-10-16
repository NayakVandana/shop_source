<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'return', 'damage', 'transfer']);
            $table->integer('quantity');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable(); // Order number, PO number, etc.
            $table->json('metadata')->nullable(); // Additional data
            $table->morphs('created_by'); // User or Admin who created the movement
            $table->timestamp('movement_date')->useCurrent();
            $table->timestamps();
            
            $table->index(['product_id', 'type']);
            $table->index(['movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};