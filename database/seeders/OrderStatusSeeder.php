<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderStatus;
use Illuminate\Support\Str;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = OrderStatus::getDefaultStatuses();

        foreach ($statuses as $statusData) {
            $statusData['uuid'] = Str::uuid();
            OrderStatus::create($statusData);
        }
    }
}