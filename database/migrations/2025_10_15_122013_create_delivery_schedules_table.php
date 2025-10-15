<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_location_id')->constrained()->onDelete('cascade');
            $table->date('delivery_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('max_orders')->nullable(); // Maximum orders for this slot
            $table->integer('booked_orders')->default(0); // Currently booked orders
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_express')->default(false); // Express delivery option
            $table->enum('delivery_type', ['standard', 'express', 'scheduled', 'same_day', 'next_day'])->default('standard');
            $table->text('notes')->nullable();
            $table->json('time_slots')->nullable(); // Available time slots
            $table->timestamp('cutoff_time')->nullable(); // Order cutoff time
            $table->timestamps();
            
            $table->index(['product_id', 'delivery_date']);
            $table->index(['delivery_location_id', 'delivery_date']);
            $table->index(['delivery_date', 'is_available']);
            $table->unique(['product_id', 'delivery_location_id', 'delivery_date'], 'delivery_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_schedules');
    }
};