<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('file_path'); // Path in storage (relative path for local, full path for S3)
            $table->string('file_name')->nullable(); // Original filename
            $table->string('mime_type')->nullable(); // MIME type
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes
            $table->string('disk')->default('public'); // Storage disk: 'public' or 's3'
            $table->string('url')->nullable(); // Full URL to access the media
            $table->integer('sort_order')->default(0); // For ordering images/videos
            $table->boolean('is_primary')->default(false); // Primary image/video
            $table->timestamps();

            // Indexes for better performance
            $table->index(['product_id', 'type']);
            $table->index(['product_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
