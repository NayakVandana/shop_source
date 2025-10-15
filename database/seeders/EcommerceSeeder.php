<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Discount;
use App\Models\DeliveryLocation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@shop.com',
            'mobile' => '1234567890',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_admin' => true,
            'is_registered' => true,
            'is_active' => true,
            'uuid' => Str::uuid(),
        ]);

        // Create sample user
        User::create([
            'name' => 'John Doe',
            'email' => 'user@shop.com',
            'mobile' => '0987654321',
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_admin' => false,
            'is_registered' => true,
            'is_active' => true,
            'uuid' => Str::uuid(),
        ]);

        // Create categories
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic devices and gadgets',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
                'description' => 'Fashion and apparel',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Books',
                'slug' => 'books',
                'description' => 'Books and literature',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Home improvement and garden supplies',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            $categoryData['uuid'] = Str::uuid();
            Category::create($categoryData);
        }

        // Create products
        $products = [
            [
                'name' => 'Smartphone',
                'slug' => 'smartphone',
                'description' => 'Latest smartphone with advanced features',
                'short_description' => 'High-end smartphone',
                'price' => 599.99,
                'sale_price' => 499.99,
                'sku' => 'PHONE-001',
                'stock_quantity' => 50,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.2,
                'dimensions' => '15x7x0.8 cm',
                'images' => ['phone1.jpg', 'phone2.jpg'],
                'is_featured' => true,
                'is_active' => true,
                'category_id' => 1,
            ],
            [
                'name' => 'Laptop',
                'slug' => 'laptop',
                'description' => 'High-performance laptop for work and gaming',
                'short_description' => 'Gaming laptop',
                'price' => 1299.99,
                'sale_price' => null,
                'sku' => 'LAPTOP-001',
                'stock_quantity' => 25,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 2.5,
                'dimensions' => '35x25x2 cm',
                'images' => ['laptop1.jpg', 'laptop2.jpg'],
                'is_featured' => true,
                'is_active' => true,
                'category_id' => 1,
            ],
            [
                'name' => 'T-Shirt',
                'slug' => 't-shirt',
                'description' => 'Comfortable cotton t-shirt',
                'short_description' => 'Cotton t-shirt',
                'price' => 19.99,
                'sale_price' => 14.99,
                'sku' => 'TSHIRT-001',
                'stock_quantity' => 100,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.2,
                'dimensions' => 'M',
                'images' => ['tshirt1.jpg'],
                'is_featured' => false,
                'is_active' => true,
                'category_id' => 2,
            ],
            [
                'name' => 'Programming Book',
                'slug' => 'programming-book',
                'description' => 'Learn programming with this comprehensive guide',
                'short_description' => 'Programming guide',
                'price' => 49.99,
                'sale_price' => null,
                'sku' => 'BOOK-001',
                'stock_quantity' => 75,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.8,
                'dimensions' => '23x15x3 cm',
                'images' => ['book1.jpg'],
                'is_featured' => false,
                'is_active' => true,
                'category_id' => 3,
            ],
            [
                'name' => 'Garden Tools Set',
                'slug' => 'garden-tools-set',
                'description' => 'Complete set of garden tools for your backyard',
                'short_description' => 'Garden tools',
                'price' => 79.99,
                'sale_price' => 59.99,
                'sku' => 'GARDEN-001',
                'stock_quantity' => 30,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 3.5,
                'dimensions' => '40x20x10 cm',
                'images' => ['garden1.jpg', 'garden2.jpg'],
                'is_featured' => true,
                'is_active' => true,
                'category_id' => 4,
            ],
        ];

        foreach ($products as $productData) {
            $productData['uuid'] = Str::uuid();
            Product::create($productData);
        }

        // Create discounts/coupons
        $discounts = [
            [
                'name' => 'Welcome Discount',
                'code' => 'WELCOME10',
                'description' => 'Welcome discount for new customers',
                'type' => 'percentage',
                'value' => 10,
                'minimum_amount' => 50,
                'max_discount_amount' => 25,
                'usage_limit' => 100,
                'used_count' => 0,
                'user_limit' => 1,
                'first_time_only' => true,
                'stackable' => false,
                'applicable_products' => null,
                'applicable_categories' => null,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'is_active' => true,
            ],
            [
                'name' => 'Summer Sale',
                'code' => 'SUMMER20',
                'description' => 'Get 20% off on all summer items',
                'type' => 'percentage',
                'value' => 20,
                'minimum_amount' => 100,
                'max_discount_amount' => 50,
                'usage_limit' => 50,
                'used_count' => 0,
                'user_limit' => 2,
                'first_time_only' => false,
                'stackable' => false,
                'applicable_products' => null,
                'applicable_categories' => null,
                'starts_at' => now(),
                'expires_at' => now()->addDays(60),
                'is_active' => true,
            ],
            [
                'name' => 'Electronics Special',
                'code' => 'ELECTRONICS15',
                'description' => '15% off on electronics category',
                'type' => 'percentage',
                'value' => 15,
                'minimum_amount' => 200,
                'max_discount_amount' => 100,
                'usage_limit' => 200,
                'used_count' => 0,
                'user_limit' => 2,
                'first_time_only' => false,
                'stackable' => true,
                'applicable_products' => null,
                'applicable_categories' => [1], // Electronics category
                'starts_at' => now(),
                'expires_at' => now()->addDays(45),
                'is_active' => true,
            ],
            [
                'name' => 'Flash Sale',
                'code' => 'FLASH50',
                'description' => 'Limited time flash sale',
                'type' => 'fixed',
                'value' => 50,
                'minimum_amount' => 300,
                'max_discount_amount' => null,
                'usage_limit' => 25,
                'used_count' => 0,
                'user_limit' => 1,
                'first_time_only' => false,
                'stackable' => false,
                'applicable_products' => null,
                'applicable_categories' => null,
                'starts_at' => now(),
                'expires_at' => now()->addDays(7),
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $discountData) {
            $discountData['uuid'] = Str::uuid();
            Discount::create($discountData);
        }

        // Create delivery locations
        $locations = [
            [
                'name' => 'Mumbai Central',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '400001',
                'latitude' => 19.0760,
                'longitude' => 72.8777,
                'address' => 'Mumbai Central Station Area',
                'is_active' => true,
                'delivery_radius_km' => 15,
                'delivery_fee' => 50,
                'estimated_delivery_days' => 2,
            ],
            [
                'name' => 'Delhi Central',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'country' => 'India',
                'postal_code' => '110001',
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'address' => 'Connaught Place Area',
                'is_active' => true,
                'delivery_radius_km' => 20,
                'delivery_fee' => 60,
                'estimated_delivery_days' => 3,
            ],
            [
                'name' => 'Bangalore Tech Park',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'country' => 'India',
                'postal_code' => '560001',
                'latitude' => 12.9716,
                'longitude' => 77.5946,
                'address' => 'Electronic City Area',
                'is_active' => true,
                'delivery_radius_km' => 12,
                'delivery_fee' => 40,
                'estimated_delivery_days' => 2,
            ],
            [
                'name' => 'Chennai Central',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'country' => 'India',
                'postal_code' => '600001',
                'latitude' => 13.0827,
                'longitude' => 80.2707,
                'address' => 'Central Railway Station Area',
                'is_active' => true,
                'delivery_radius_km' => 18,
                'delivery_fee' => 45,
                'estimated_delivery_days' => 3,
            ],
            [
                'name' => 'Kolkata Central',
                'city' => 'Kolkata',
                'state' => 'West Bengal',
                'country' => 'India',
                'postal_code' => '700001',
                'latitude' => 22.5726,
                'longitude' => 88.3639,
                'address' => 'Howrah Station Area',
                'is_active' => true,
                'delivery_radius_km' => 16,
                'delivery_fee' => 55,
                'estimated_delivery_days' => 4,
            ],
        ];

        foreach ($locations as $locationData) {
            $locationData['uuid'] = Str::uuid();
            DeliveryLocation::create($locationData);
        }

        // Assign delivery locations to products
        $products = Product::all();
        $locations = DeliveryLocation::all();

        foreach ($products as $product) {
            // Assign 2-3 random locations to each product
            $randomLocations = $locations->random(rand(2, 3));
            $syncData = [];
            
            foreach ($randomLocations as $location) {
                $syncData[$location->id] = [
                    'delivery_fee' => $location->delivery_fee + rand(-10, 10), // Vary delivery fee
                    'estimated_delivery_days' => $location->estimated_delivery_days + rand(-1, 1), // Vary delivery days
                    'is_available' => true
                ];
            }
            
            $product->deliveryLocations()->sync($syncData);
        }
    }
}