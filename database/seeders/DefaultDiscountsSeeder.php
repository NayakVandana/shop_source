<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DefaultDiscountsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for Discounts table.
     */
    public function run(): void
    {
        // Check if discounts already exist
        if (Discount::count() > 0) {
            $this->command->info('Discounts table already has data. Skipping DefaultDiscountsSeeder.');
            return;
        }

        // Get some products to attach discounts to
        $products = Product::take(5)->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please seed products first.');
            return;
        }

        $discounts = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Summer Sale - 20% Off',
                'description' => 'Get 20% off on selected electronics and home products. Limited time offer!',
                'type' => 'percentage',
                'value' => 20.00,
                'min_purchase_amount' => 50.00,
                'max_discount_amount' => 100.00,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(30),
                'usage_limit' => 1000,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Flash Sale - $50 Off',
                'description' => 'Save $50 on purchases over $200. Perfect for big shopping!',
                'type' => 'fixed',
                'value' => 50.00,
                'min_purchase_amount' => 200.00,
                'max_discount_amount' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(7),
                'usage_limit' => 500,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'New Customer Discount',
                'description' => 'Welcome discount for new customers. Get 15% off your first purchase!',
                'type' => 'percentage',
                'value' => 15.00,
                'min_purchase_amount' => null,
                'max_discount_amount' => 75.00,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addDays(60),
                'usage_limit' => null, // Unlimited
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Weekend Special - 25% Off',
                'description' => 'Weekend special discount on clothing and accessories. Valid only on weekends.',
                'type' => 'percentage',
                'value' => 25.00,
                'min_purchase_amount' => 30.00,
                'max_discount_amount' => 150.00,
                'start_date' => Carbon::now()->startOfWeek(),
                'end_date' => Carbon::now()->endOfWeek()->addWeeks(4),
                'usage_limit' => 2000,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Bulk Purchase Discount',
                'description' => 'Save $25 when you purchase 3 or more items. Great for bulk buyers!',
                'type' => 'fixed',
                'value' => 25.00,
                'min_purchase_amount' => 100.00,
                'max_discount_amount' => null,
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(45),
                'usage_limit' => 800,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Expired Discount (Test)',
                'description' => 'This discount has expired for testing purposes.',
                'type' => 'percentage',
                'value' => 10.00,
                'min_purchase_amount' => null,
                'max_discount_amount' => null,
                'start_date' => Carbon::now()->subDays(60),
                'end_date' => Carbon::now()->subDays(30),
                'usage_limit' => 100,
                'usage_count' => 50,
                'is_active' => false,
            ],
        ];

        foreach ($discounts as $index => $discountData) {
            $discount = Discount::create($discountData);
            
            // Attach first 3 discounts to products (for testing)
            if ($index < 3 && $products->count() > 0) {
                // Attach discount to first 2-3 products
                $productsToAttach = $products->take(2 + ($index % 2))->pluck('id')->toArray();
                $discount->products()->attach($productsToAttach);
            }
        }

        $this->command->info('Default discounts seeded successfully!');
    }
}
