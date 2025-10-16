<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['return', 'exchange', 'refund'])->default('return');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('return_tracking_number')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('images')->nullable(); // Return/exchange images
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('processed_by_type')->nullable(); // admin, system
            $table->unsignedBigInteger('processed_by_id')->nullable();
            
            // Replacement fields
            $table->foreignId('replacement_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('replacement_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->decimal('return_shipping_cost', 8, 2)->nullable()->default(0);
            $table->string('refund_method')->nullable();
            $table->string('refund_reference')->nullable();
            $table->integer('return_window_days')->default(30);
            $table->boolean('is_defective')->default(false);
            $table->text('condition_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_returns');
    }
};