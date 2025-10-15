<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
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
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_number',
                'delivery_company',
                'shipped_at',
                'delivered_at',
                'estimated_delivery_date',
                'order_type',
                'is_cancellable',
                'is_returnable',
                'cancelled_at',
                'cancellation_reason',
                'delivery_location_id',
                'order_status_id',
                'delivery_notes',
                'delivery_contact_phone',
                'special_instructions'
            ]);
        });
    }
};