<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates user_permissions table to store user permissions.
     * Permissions are stored as JSON array of objects in format:
     * [{"module": "products", "action": "view", "permission": "products:view"}, ...]
     * Example: [{"module": "products", "action": "view", "permission": "products:view"}]
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role', 191); // Store role for this user
            $table->json('permissions')->nullable(); // Array of permission identifiers (module:action format)
            $table->timestamps();
            
            $table->unique(['user_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};

