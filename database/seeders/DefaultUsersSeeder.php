<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultUsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for Users table.
     */
    public function run(): void
    {
        // Check if users already exist
        if (User::count() > 0) {
            $this->command->info('Users table already has data. Skipping DefaultUsersSeeder.');
            return;
        }

        $users = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'mobile' => '+1234567890',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_registered' => true,
                'is_active' => true,
                'is_admin' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Test User',
                'email' => 'test@example.com',
                'mobile' => '+1234567891',
                'password' => bcrypt('password'),
                'role' => 'user',
                'is_registered' => true,
                'is_active' => true,
                'is_admin' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'mobile' => '+1234567892',
                'password' => bcrypt('password'),
                'role' => 'user',
                'is_registered' => true,
                'is_active' => true,
                'is_admin' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'mobile' => '+1234567893',
                'password' => bcrypt('password'),
                'role' => 'user',
                'is_registered' => true,
                'is_active' => true,
                'is_admin' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'mobile' => '+1234567894',
                'password' => bcrypt('password'),
                'role' => 'super_admin',
                'is_registered' => true,
                'is_active' => true,
                'is_admin' => true,
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        $this->command->info('Default users seeded successfully!');
    }
}
