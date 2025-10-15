<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_delivery_locations', function (Blueprint $table) {
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by_type')->nullable(); // admin, system
            $table->unsignedBigInteger('cancelled_by_id')->nullable();
            $table->text('cancellation_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_delivery_locations', function (Blueprint $table) {
            $table->dropColumn([
                'is_cancelled',
                'cancellation_reason',
                'cancelled_at',
                'cancelled_by_type',
                'cancelled_by_id',
                'cancellation_notes'
            ]);
        });
    }
};