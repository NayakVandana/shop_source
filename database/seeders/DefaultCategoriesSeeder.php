<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultCategoriesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for Categories table.
     */
    public function run(): void
    {
        // Check if categories already exist
        if (Category::count() > 0) {
            $this->command->info('Categories table already has data. Skipping DefaultCategoriesSeeder.');
            return;
        }

        $categories = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets including smartphones, laptops, tablets, and accessories.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel for men, women, and children including shirts, pants, dresses, and accessories.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Home & Kitchen',
                'slug' => 'home-kitchen',
                'description' => 'Home essentials and kitchen appliances including furniture, cookware, and home decor.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Sports equipment, outdoor gear, fitness accessories, and athletic wear.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Books & Media',
                'slug' => 'books-media',
                'description' => 'Books, e-books, movies, music, and other media products.',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty-personal-care',
                'description' => 'Cosmetics, skincare products, personal care items, and beauty accessories.',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Toys & Games',
                'slug' => 'toys-games',
                'description' => 'Toys, board games, video games, and entertainment products for all ages.',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car accessories, parts, tools, and automotive supplies.',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Default categories seeded successfully!');
    }
}
