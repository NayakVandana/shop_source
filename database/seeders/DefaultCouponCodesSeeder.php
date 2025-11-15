<?php

namespace Database\Seeders;

use App\Models\CouponCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DefaultCouponCodesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for Coupon Codes table.
     */
    public function run(): void
    {
        // Check if coupon codes already exist
        if (CouponCode::count() > 0) {
            $this->command->info('Coupon codes table already has data. Skipping DefaultCouponCodesSeeder.');
            return;
        }

        $coupons = [
            [
                'uuid' => Str::uuid(),
                'code' => 'WELCOME20',
                'name' => 'Welcome Discount',
                'description' => 'Get 20% off on your first order. Use code WELCOME20 at checkout.',
                'type' => 'percentage',
                'value' => 20.00,
                'min_purchase_amount' => 50.00,
                'max_discount_amount' => 100.00,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => Carbon::now()->addDays(60),
                'usage_limit' => 1000,
                'usage_limit_per_user' => 1,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'SAVE50',
                'name' => 'Save $50',
                'description' => 'Save $50 on orders over $200. Limited time offer!',
                'type' => 'fixed',
                'value' => 50.00,
                'min_purchase_amount' => 200.00,
                'max_discount_amount' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'usage_limit' => 500,
                'usage_limit_per_user' => 2,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'SUMMER25',
                'name' => 'Summer Sale',
                'description' => 'Enjoy 25% off on all summer items. Perfect for the season!',
                'type' => 'percentage',
                'value' => 25.00,
                'min_purchase_amount' => 75.00,
                'max_discount_amount' => 200.00,
                'start_date' => Carbon::now()->subDays(3),
                'end_date' => Carbon::now()->addDays(45),
                'usage_limit' => 2000,
                'usage_limit_per_user' => 3,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'FLASH30',
                'name' => 'Flash Sale',
                'description' => 'Flash sale! Get 30% off. Hurry, limited stock!',
                'type' => 'percentage',
                'value' => 30.00,
                'min_purchase_amount' => 100.00,
                'max_discount_amount' => 150.00,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(7),
                'usage_limit' => 300,
                'usage_limit_per_user' => 1,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'FREESHIP',
                'name' => 'Free Shipping',
                'description' => 'Free shipping on orders over $100. Use code FREESHIP.',
                'type' => 'fixed',
                'value' => 15.00, // Shipping cost
                'min_purchase_amount' => 100.00,
                'max_discount_amount' => 15.00,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addDays(90),
                'usage_limit' => null, // Unlimited
                'usage_limit_per_user' => null, // Unlimited per user
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'STUDENT15',
                'name' => 'Student Discount',
                'description' => 'Special 15% discount for students. Valid ID required.',
                'type' => 'percentage',
                'value' => 15.00,
                'min_purchase_amount' => null,
                'max_discount_amount' => 50.00,
                'start_date' => Carbon::now()->subDays(7),
                'end_date' => Carbon::now()->addDays(180),
                'usage_limit' => 5000,
                'usage_limit_per_user' => 5,
                'usage_count' => 0,
                'is_active' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'EXPIRED10',
                'name' => 'Expired Coupon (Test)',
                'description' => 'This coupon has expired for testing purposes.',
                'type' => 'percentage',
                'value' => 10.00,
                'min_purchase_amount' => null,
                'max_discount_amount' => null,
                'start_date' => Carbon::now()->subDays(60),
                'end_date' => Carbon::now()->subDays(30),
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'usage_count' => 75,
                'is_active' => false,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'INACTIVE',
                'name' => 'Inactive Coupon (Test)',
                'description' => 'This coupon is inactive for testing purposes.',
                'type' => 'percentage',
                'value' => 20.00,
                'min_purchase_amount' => null,
                'max_discount_amount' => null,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'usage_count' => 0,
                'is_active' => false,
            ],
        ];

        foreach ($coupons as $coupon) {
            CouponCode::create($coupon);
        }

        $this->command->info('Default coupon codes seeded successfully!');
    }
}
