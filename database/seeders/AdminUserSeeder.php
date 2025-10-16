<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'mobile' => '1234567890',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_registered' => true,
            'is_active' => true,
            'is_admin' => true,
        ]);

        // Create regular user
        User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'mobile' => '0987654321',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_registered' => true,
            'is_active' => true,
            'is_admin' => false,
        ]);
    }
}
