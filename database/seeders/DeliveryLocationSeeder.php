<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeliveryLocation;
use App\Models\Product;
use Illuminate\Support\Str;

class DeliveryLocationSeeder extends Seeder
{
    public function run(): void
    {
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