<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OrderStatus extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'color', 'is_active', 'sort_order', 'is_system', 'uuid'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($status) {
            if (empty($status->uuid)) {
                $status->uuid = Str::uuid();
            }
            if (empty($status->slug)) {
                $status->slug = Str::slug($status->name);
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(OrderTimeline::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get default order statuses
     */
    public static function getDefaultStatuses()
    {
        return [
            [
                'name' => 'Pending',
                'slug' => 'pending',
                'description' => 'Order is pending confirmation',
                'color' => '#F59E0B',
                'is_system' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Confirmed',
                'slug' => 'confirmed',
                'description' => 'Order has been confirmed',
                'color' => '#3B82F6',
                'is_system' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Processing',
                'slug' => 'processing',
                'description' => 'Order is being processed',
                'color' => '#8B5CF6',
                'is_system' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Shipped',
                'slug' => 'shipped',
                'description' => 'Order has been shipped',
                'color' => '#10B981',
                'is_system' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Delivered',
                'slug' => 'delivered',
                'description' => 'Order has been delivered',
                'color' => '#059669',
                'is_system' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Cancelled',
                'slug' => 'cancelled',
                'description' => 'Order has been cancelled',
                'color' => '#EF4444',
                'is_system' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Returned',
                'slug' => 'returned',
                'description' => 'Order has been returned',
                'color' => '#6B7280',
                'is_system' => true,
                'sort_order' => 7,
            ],
        ];
    }
}