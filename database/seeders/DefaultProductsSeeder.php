<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultProductsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for Products table.
     */
    public function run(): void
    {
        // Check if products already exist
        if (Product::count() > 0) {
            $this->command->info('Products table already has data. Skipping DefaultProductsSeeder.');
            return;
        }

        // Get categories
        $electronics = Category::where('slug', 'electronics')->first();
        $clothing = Category::where('slug', 'clothing')->first();
        $homeKitchen = Category::where('slug', 'home-kitchen')->first();
        $sports = Category::where('slug', 'sports-outdoors')->first();
        $books = Category::where('slug', 'books-media')->first();
        $beauty = Category::where('slug', 'beauty-personal-care')->first();

        $products = [
            // Electronics
            [
                'uuid' => Str::uuid(),
                'name' => 'Smartphone Pro Max',
                'slug' => 'smartphone-pro-max',
                'description' => 'Latest generation smartphone with advanced features, high-resolution camera, and long-lasting battery. Perfect for professionals and tech enthusiasts.',
                'short_description' => 'Latest generation smartphone with advanced features',
                'price' => 999.99,
                'sale_price' => 899.99,
                'sku' => 'SKU-ELEC001',
                'stock_quantity' => 50,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.2,
                'dimensions' => '15cm x 7cm x 0.8cm',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $electronics?->id,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Wireless Bluetooth Headphones',
                'slug' => 'wireless-bluetooth-headphones',
                'description' => 'Premium wireless headphones with noise cancellation, 30-hour battery life, and superior sound quality.',
                'short_description' => 'Premium wireless headphones with noise cancellation',
                'price' => 199.99,
                'sale_price' => 149.99,
                'sku' => 'SKU-ELEC002',
                'stock_quantity' => 100,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.3,
                'dimensions' => '20cm x 18cm x 8cm',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $electronics?->id,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Laptop Ultra 15',
                'slug' => 'laptop-ultra-15',
                'description' => 'High-performance laptop with 15-inch display, powerful processor, and fast SSD storage. Ideal for work and gaming.',
                'short_description' => 'High-performance laptop with 15-inch display',
                'price' => 1299.99,
                'sale_price' => null,
                'sku' => 'SKU-ELEC003',
                'stock_quantity' => 25,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 2.1,
                'dimensions' => '35cm x 24cm x 2cm',
                'is_featured' => false,
                'is_active' => true,
                'category_id' => $electronics?->id,
            ],

            // Clothing
            [
                'uuid' => Str::uuid(),
                'name' => 'Classic Cotton T-Shirt',
                'slug' => 'classic-cotton-t-shirt',
                'description' => 'Comfortable 100% cotton t-shirt available in multiple colors. Perfect for everyday wear.',
                'short_description' => 'Comfortable 100% cotton t-shirt',
                'price' => 29.99,
                'sale_price' => 19.99,
                'sku' => 'SKU-CLTH001',
                'stock_quantity' => 200,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.15,
                'dimensions' => 'Size: M',
                'is_featured' => false,
                'is_active' => true,
                'category_id' => $clothing?->id,
                'sizes' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                'colors' => ['Red', 'Blue', 'Black', 'White', 'Gray'],
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Denim Jeans',
                'slug' => 'denim-jeans',
                'description' => 'Classic fit denim jeans made from premium cotton. Durable and stylish for any occasion.',
                'short_description' => 'Classic fit denim jeans',
                'price' => 79.99,
                'sale_price' => 59.99,
                'sku' => 'SKU-CLTH002',
                'stock_quantity' => 150,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.5,
                'dimensions' => 'Size: 32x32',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $clothing?->id,
                'sizes' => ['28', '30', '32', '34', '36', '38', '40'],
                'colors' => ['Blue', 'Black', 'Light Blue'],
            ],

            // Home & Kitchen
            [
                'uuid' => Str::uuid(),
                'name' => 'Stainless Steel Cookware Set',
                'slug' => 'stainless-steel-cookware-set',
                'description' => 'Complete 10-piece cookware set with non-stick coating. Includes pots, pans, and lids.',
                'short_description' => 'Complete 10-piece cookware set',
                'price' => 149.99,
                'sale_price' => 119.99,
                'sku' => 'SKU-HOME001',
                'stock_quantity' => 75,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 5.2,
                'dimensions' => 'Various sizes',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $homeKitchen?->id,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Coffee Maker Deluxe',
                'slug' => 'coffee-maker-deluxe',
                'description' => 'Programmable coffee maker with thermal carafe. Makes up to 12 cups.',
                'short_description' => 'Programmable coffee maker',
                'price' => 89.99,
                'sale_price' => null,
                'sku' => 'SKU-HOME002',
                'stock_quantity' => 60,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 3.5,
                'dimensions' => '30cm x 25cm x 35cm',
                'is_featured' => false,
                'is_active' => true,
                'category_id' => $homeKitchen?->id,
            ],

            // Sports & Outdoors
            [
                'uuid' => Str::uuid(),
                'name' => 'Yoga Mat Premium',
                'slug' => 'yoga-mat-premium',
                'description' => 'Extra thick non-slip yoga mat with carrying strap. Perfect for yoga, pilates, and exercise.',
                'short_description' => 'Extra thick non-slip yoga mat',
                'price' => 39.99,
                'sale_price' => 29.99,
                'sku' => 'SKU-SPRT001',
                'stock_quantity' => 120,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 1.2,
                'dimensions' => '183cm x 61cm x 0.6cm',
                'is_featured' => false,
                'is_active' => true,
                'category_id' => $sports?->id,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Running Shoes Pro',
                'slug' => 'running-shoes-pro',
                'description' => 'Lightweight running shoes with cushioned sole and breathable mesh upper. Perfect for long runs.',
                'short_description' => 'Lightweight running shoes',
                'price' => 129.99,
                'sale_price' => 99.99,
                'sku' => 'SKU-SPRT002',
                'stock_quantity' => 80,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.3,
                'dimensions' => 'Size: 10',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $sports?->id,
            ],

            // Books & Media
            [
                'uuid' => Str::uuid(),
                'name' => 'Best Seller Novel Collection',
                'slug' => 'best-seller-novel-collection',
                'description' => 'Set of 5 bestselling novels in hardcover. Perfect for book lovers and collectors.',
                'short_description' => 'Set of 5 bestselling novels',
                'price' => 79.99,
                'sale_price' => 59.99,
                'sku' => 'SKU-BOOK001',
                'stock_quantity' => 40,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 2.5,
                'dimensions' => '23cm x 15cm x 8cm',
                'is_featured' => false,
                'is_active' => true,
                'category_id' => $books?->id,
            ],

            // Beauty & Personal Care
            [
                'uuid' => Str::uuid(),
                'name' => 'Skincare Essentials Set',
                'slug' => 'skincare-essentials-set',
                'description' => 'Complete skincare routine set including cleanser, toner, moisturizer, and serum.',
                'short_description' => 'Complete skincare routine set',
                'price' => 89.99,
                'sale_price' => 69.99,
                'sku' => 'SKU-BTY001',
                'stock_quantity' => 90,
                'manage_stock' => true,
                'in_stock' => true,
                'weight' => 0.8,
                'dimensions' => 'Gift box set',
                'is_featured' => true,
                'is_active' => true,
                'category_id' => $beauty?->id,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::create($productData);
            
            // Create product variations for clothing products with sizes and colors
            if ($product->category_id === $clothing?->id && !empty($product->sizes) && !empty($product->colors)) {
                $variations = [];
                $totalVariationStock = 0; // Track total stock across all variations
                
                foreach ($product->sizes as $size) {
                    foreach ($product->colors as $color) {
                        // Random stock quantity between 10-50 for each variation
                        $stockQuantity = rand(10, 50);
                        $totalVariationStock += $stockQuantity;
                        
                        $variations[] = [
                            'product_id' => $product->id,
                            'size' => $size,
                            'color' => $color,
                            'stock_quantity' => $stockQuantity,
                            'in_stock' => $stockQuantity > 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                // Insert variations in batches
                if (!empty($variations)) {
                    ProductVariation::insert($variations);
                    
                    // Update product's general stock_quantity to sum of all variations
                    // This represents total available stock across all size-color combinations
                    // The general stock is used as fallback when no specific variation is found
                    $product->update([
                        'stock_quantity' => $totalVariationStock,
                        'in_stock' => $totalVariationStock > 0,
                    ]);
                }
            }
        }

        $this->command->info('Default products seeded successfully!');
        $this->command->info('Product variations created for clothing products!');
        $this->command->info('Note: For clothing products, products.stock_quantity = sum of all product_variations.stock_quantity');
    }
}
