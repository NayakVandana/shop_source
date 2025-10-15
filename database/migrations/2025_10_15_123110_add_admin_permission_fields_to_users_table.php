<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('admin_role_id')->nullable()->constrained()->onDelete('set null');
            $table->json('permissions')->nullable(); // Custom permissions for specific users
            $table->boolean('is_super_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_role_id',
                'permissions',
                'is_super_admin',
                'last_login_at',
                'last_login_ip'
            ]);
        });
    }
};