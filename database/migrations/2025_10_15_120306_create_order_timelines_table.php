<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_timelines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_status_id')->constrained()->onDelete('cascade');
            $table->string('status');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('status_date');
            $table->string('updated_by_type')->nullable(); // admin, user, system
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->boolean('is_visible_to_customer')->default(true);
            $table->timestamps();
            
            $table->index(['order_id', 'status_date']);
            $table->index(['updated_by_type', 'updated_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_timelines');
    }
};