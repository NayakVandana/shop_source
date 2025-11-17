<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductColor;
use App\Models\Category;
use App\Helpers\ProductSizeHelper;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultProductSizesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for ProductSizes table.
     */
    public function run(): void
    {
        $skipSizes = ProductSize::count() > 0;
        $skipColors = ProductColor::count() > 0;

        if ($skipSizes) {
            $this->command->info('Product sizes table already has data. Skipping size seeding.');
        }

        if ($skipColors) {
            $this->command->info('Product colors table already has data. Skipping color seeding.');
        }

        if ($skipSizes && $skipColors) {
            $this->command->info('Both sizes and colors already exist. Skipping seeder.');
            return;
        }

        // Get clothing category - try multiple ways
        $clothing = Category::where(function($query) {
            $query->where('slug', 'clothing')
                  ->orWhere('slug', 'clothing-apparel')
                  ->orWhere('name', 'like', '%clothing%')
                  ->orWhere('name', 'like', '%apparel%')
                  ->orWhere('name', 'Clothing');
        })->first();
        
        if (!$clothing) {
            $this->command->warn('Clothing category not found. Trying to find by name or creating products without category filter...');
            // Continue anyway - we'll seed all products that might need sizes/colors
        }

        // Get all clothing products
        if ($clothing) {
            $clothingProducts = Product::where('category_id', $clothing->id)->get();
        } else {
            // If no clothing category found, try to find products by name/description
            $clothingProducts = Product::where(function($query) {
                $query->where('name', 'like', '%shirt%')
                      ->orWhere('name', 'like', '%pant%')
                      ->orWhere('name', 'like', '%dress%')
                      ->orWhere('name', 'like', '%jean%')
                      ->orWhere('name', 'like', '%t-shirt%')
                      ->orWhere('name', 'like', '%clothing%')
                      ->orWhere('name', 'like', '%apparel%')
                      ->orWhere('description', 'like', '%clothing%')
                      ->orWhere('description', 'like', '%apparel%');
            })->get();
        }

        if ($clothingProducts->isEmpty()) {
            $this->command->warn('No clothing products found. Skipping product sizes and colors seeding.');
            return;
        }

        $sizesCreated = 0;
        $colorsCreated = 0;

        // Common colors for clothing
        $commonColors = [
            ['color' => 'Black', 'color_code' => '#000000'],
            ['color' => 'White', 'color_code' => '#FFFFFF'],
            ['color' => 'Red', 'color_code' => '#FF0000'],
            ['color' => 'Blue', 'color_code' => '#0000FF'],
            ['color' => 'Green', 'color_code' => '#008000'],
            ['color' => 'Yellow', 'color_code' => '#FFFF00'],
            ['color' => 'Pink', 'color_code' => '#FFC0CB'],
            ['color' => 'Purple', 'color_code' => '#800080'],
            ['color' => 'Orange', 'color_code' => '#FFA500'],
            ['color' => 'Gray', 'color_code' => '#808080'],
            ['color' => 'Brown', 'color_code' => '#A52A2A'],
            ['color' => 'Navy', 'color_code' => '#000080'],
        ];
        
        foreach ($clothingProducts as $product) {
            // Determine sizes based on product name and description
            $productText = strtolower($product->name . ' ' . ($product->description ?? ''));
            $availableSizes = ProductSizeHelper::getAvailableSizesForCategory($productText);

            // If no specific category detected, use default sizes for generic clothing
            // Check if it's a generic product (like "Classic Cotton T-Shirt" or "Denim Jeans")
            if (count($availableSizes) === 7) { // Default generic sizes
                // Use a subset of common sizes for generic clothing
                $availableSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            }

            // Limit to reasonable number of sizes (max 15 sizes per product)
            $availableSizes = array_slice($availableSizes, 0, 15);

            // Create sizes for this product (only if sizes don't exist and not skipping)
            if (!$skipSizes && ProductSize::where('product_id', $product->id)->count() === 0) {
                foreach ($availableSizes as $index => $size) {
                    // Random stock quantity between 10-50 for each size
                    $stockQuantity = rand(10, 50);
                    
                    ProductSize::create([
                        'product_id' => $product->id,
                        'size' => $size,
                        'stock_quantity' => $stockQuantity,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]);
                    
                    $sizesCreated++;
                }
            }

            // Create colors for this product (only if colors don't exist and not skipping)
            if (!$skipColors && ProductColor::where('product_id', $product->id)->count() === 0) {
                // Select 3-6 random colors for each product
                $productColors = (array) array_rand($commonColors, min(rand(3, 6), count($commonColors)));
                if (!is_array($productColors)) {
                    $productColors = [$productColors];
                }
                
                foreach ($productColors as $index => $colorIndex) {
                    $colorData = $commonColors[$colorIndex];
                    $stockQuantity = rand(10, 50);
                    
                    ProductColor::create([
                        'product_id' => $product->id,
                        'color' => $colorData['color'],
                        'color_code' => $colorData['color_code'],
                        'stock_quantity' => $stockQuantity,
                        'is_active' => true,
                        'sort_order' => $index,
                    ]);
                    
                    $colorsCreated++;
                }
            }
        }

        // Also add sizes and colors to sports products that might need sizes (like shoes, athletic wear)
        $sports = Category::where(function($query) {
            $query->where('slug', 'sports-outdoors')
                  ->orWhere('slug', 'sports')
                  ->orWhere('name', 'like', '%sport%');
        })->first();
        
        if ($sports) {
            $sportsProducts = Product::where('category_id', $sports->id)
                ->where(function($query) {
                    $query->where('name', 'like', '%shoe%')
                          ->orWhere('name', 'like', '%shirt%')
                          ->orWhere('name', 'like', '%pant%')
                          ->orWhere('name', 'like', '%wear%');
                })
                ->get();

            foreach ($sportsProducts as $product) {
                // For shoes, use numeric sizes
                if (stripos($product->name, 'shoe') !== false) {
                    $shoeSizes = ['7', '8', '9', '10', '11', '12', '13'];
                    if (!$skipSizes && ProductSize::where('product_id', $product->id)->count() === 0) {
                        foreach ($shoeSizes as $index => $size) {
                            ProductSize::create([
                                'product_id' => $product->id,
                                'size' => $size,
                                'stock_quantity' => rand(5, 30),
                                'is_active' => true,
                                'sort_order' => $index,
                            ]);
                            $sizesCreated++;
                        }
                    }
                } else {
                    // For other sports wear, use standard sizes
                    $standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                    if (!$skipSizes && ProductSize::where('product_id', $product->id)->count() === 0) {
                        foreach ($standardSizes as $index => $size) {
                            ProductSize::create([
                                'product_id' => $product->id,
                                'size' => $size,
                                'stock_quantity' => rand(10, 40),
                                'is_active' => true,
                                'sort_order' => $index,
                            ]);
                            $sizesCreated++;
                        }
                    }
                }

                // Add colors to sports products
                if (!$skipColors && ProductColor::where('product_id', $product->id)->count() === 0) {
                    $sportsColors = [
                        ['color' => 'Black', 'color_code' => '#000000'],
                        ['color' => 'White', 'color_code' => '#FFFFFF'],
                        ['color' => 'Red', 'color_code' => '#FF0000'],
                        ['color' => 'Blue', 'color_code' => '#0000FF'],
                        ['color' => 'Gray', 'color_code' => '#808080'],
                    ];
                    $selectedColors = (array) array_rand($sportsColors, min(rand(2, 4), count($sportsColors)));
                    if (!is_array($selectedColors)) {
                        $selectedColors = [$selectedColors];
                    }
                    
                    foreach ($selectedColors as $index => $colorIndex) {
                        $colorData = $sportsColors[$colorIndex];
                        ProductColor::create([
                            'product_id' => $product->id,
                            'color' => $colorData['color'],
                            'color_code' => $colorData['color_code'],
                            'stock_quantity' => rand(10, 40),
                            'is_active' => true,
                            'sort_order' => $index,
                        ]);
                        $colorsCreated++;
                    }
                }
            }
        }

        $this->command->info("Product sizes and colors seeded successfully! Created {$sizesCreated} size records and {$colorsCreated} color records.");
    }
}
