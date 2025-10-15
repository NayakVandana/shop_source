<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_issues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_location_id')->constrained()->onDelete('cascade');
            $table->enum('issue_type', ['product_unavailable', 'delivery_location_issue', 'logistics_problem', 'weather_issue', 'address_issue', 'customer_unavailable', 'other'])->default('other');
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['reported', 'investigating', 'resolved', 'cancelled'])->default('reported');
            $table->enum('resolution', ['delivery_cancelled', 'delivery_delayed', 'delivery_rerouted', 'product_replaced', 'refund_issued', 'other'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('reported_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('reported_by_type'); // admin, system, customer
            $table->unsignedBigInteger('reported_by_id');
            $table->string('resolved_by_type')->nullable(); // admin, system
            $table->unsignedBigInteger('resolved_by_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['order_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index(['delivery_location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_issues');
    }
};