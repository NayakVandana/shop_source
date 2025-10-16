<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic devices and gadgets',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $clothing = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
            'description' => 'Fashion and clothing items',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $books = Category::create([
            'name' => 'Books',
            'slug' => 'books',
            'description' => 'Books and educational materials',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create products
        Product::create([
            'name' => 'Smartphone',
            'slug' => 'smartphone',
            'description' => 'Latest smartphone with advanced features',
            'short_description' => 'High-end smartphone',
            'price' => 599.99,
            'sale_price' => 549.99,
            'sku' => 'PHONE001',
            'stock_quantity' => 50,
            'manage_stock' => true,
            'in_stock' => true,
            'weight' => 0.2,
            'dimensions' => '15x7x0.8 cm',
            'images' => ['products/smartphone.jpg'],
            'videos' => [],
            'is_featured' => true,
            'is_active' => true,
            'category_id' => $electronics->id,
        ]);

        Product::create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'description' => 'High-performance laptop for work and gaming',
            'short_description' => 'Gaming laptop',
            'price' => 1299.99,
            'sale_price' => null,
            'sku' => 'LAPTOP001',
            'stock_quantity' => 25,
            'manage_stock' => true,
            'in_stock' => true,
            'weight' => 2.5,
            'dimensions' => '35x25x2 cm',
            'images' => ['products/laptop.jpg'],
            'videos' => [],
            'is_featured' => true,
            'is_active' => true,
            'category_id' => $electronics->id,
        ]);

        Product::create([
            'name' => 'T-Shirt',
            'slug' => 't-shirt',
            'description' => 'Comfortable cotton t-shirt',
            'short_description' => 'Cotton t-shirt',
            'price' => 29.99,
            'sale_price' => 24.99,
            'sku' => 'TSHIRT001',
            'stock_quantity' => 100,
            'manage_stock' => true,
            'in_stock' => true,
            'weight' => 0.2,
            'dimensions' => 'M',
            'images' => ['products/tshirt.jpg'],
            'videos' => [],
            'is_featured' => false,
            'is_active' => true,
            'category_id' => $clothing->id,
        ]);

        Product::create([
            'name' => 'Programming Book',
            'slug' => 'programming-book',
            'description' => 'Complete guide to programming',
            'short_description' => 'Programming guide',
            'price' => 49.99,
            'sale_price' => null,
            'sku' => 'BOOK001',
            'stock_quantity' => 75,
            'manage_stock' => true,
            'in_stock' => true,
            'weight' => 0.8,
            'dimensions' => '23x15x3 cm',
            'images' => ['products/book.jpg'],
            'videos' => [],
            'is_featured' => false,
            'is_active' => true,
            'category_id' => $books->id,
        ]);
    }
}
