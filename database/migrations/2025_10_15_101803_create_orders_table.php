<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->text('shipping_address');
            $table->text('billing_address')->nullable();
            $table->string('notes')->nullable();
            
            // Delivery tracking fields
            $table->string('tracking_number')->nullable();
            $table->string('delivery_company')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('estimated_delivery_date')->nullable();
            
            // Order management fields
            $table->enum('order_type', ['normal', 'express', 'scheduled'])->default('normal');
            $table->boolean('is_cancellable')->default(true);
            $table->boolean('is_returnable')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Delivery location reference
            $table->foreignId('delivery_location_id')->nullable()->constrained()->onDelete('set null');
            
            // Order status reference
            $table->foreignId('order_status_id')->nullable()->constrained()->onDelete('set null');
            
            // Additional tracking
            $table->json('delivery_notes')->nullable();
            $table->string('delivery_contact_phone')->nullable();
            $table->text('special_instructions')->nullable();
            
            // Delivery scheduling fields
            $table->foreignId('delivery_schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->date('preferred_delivery_date')->nullable();
            $table->time('preferred_delivery_time')->nullable();
            $table->enum('delivery_type', ['standard', 'express', 'scheduled', 'same_day', 'next_day'])->default('standard');
            $table->boolean('is_express_delivery')->default(false);
            $table->decimal('express_delivery_fee', 8, 2)->default(0);
            $table->timestamp('delivery_cutoff_time')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->json('time_slot_preferences')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};