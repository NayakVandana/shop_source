<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
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
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_schedule_id',
                'preferred_delivery_date',
                'preferred_delivery_time',
                'delivery_type',
                'is_express_delivery',
                'express_delivery_fee',
                'delivery_cutoff_time',
                'delivery_instructions',
                'time_slot_preferences'
            ]);
        });
    }
};