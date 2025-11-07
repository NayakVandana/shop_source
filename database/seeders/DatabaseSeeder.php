<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 
     * This seeder calls all default data seeders in the correct order.
     * Run with: php artisan db:seed
     * Or fresh migration with seed: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');
        
        // Seed in order: Users -> Categories -> Products
        // Users can be seeded first as they don't depend on other tables
        $this->call([
            DefaultUsersSeeder::class,
            DefaultCategoriesSeeder::class,
            DefaultProductsSeeder::class,
        ]);
        
        $this->command->info('Database seeding completed successfully!');
    }
}
