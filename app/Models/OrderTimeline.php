<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class OrderTimeline extends Model
{
    protected $fillable = [
        'order_id', 'order_status_id', 'status', 'title', 'description',
        'metadata', 'status_date', 'updated_by_type', 'updated_by_id',
        'is_visible_to_customer', 'uuid'
    ];

    protected $casts = [
        'metadata' => 'array',
        'status_date' => 'datetime',
        'is_visible_to_customer' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($timeline) {
            if (empty($timeline->uuid)) {
                $timeline->uuid = Str::uuid();
            }
            if (empty($timeline->status_date)) {
                $timeline->status_date = now();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function updatedBy(): MorphTo
    {
        return $this->morphTo('updated_by');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Create a timeline entry
     */
    public static function createEntry($orderId, $status, $title, $description = null, $metadata = null, $updatedBy = null, $isVisibleToCustomer = true)
    {
        $orderStatus = OrderStatus::where('slug', $status)->first();
        
        return static::create([
            'order_id' => $orderId,
            'order_status_id' => $orderStatus?->id,
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'metadata' => $metadata,
            'updated_by_type' => $updatedBy ? get_class($updatedBy) : 'system',
            'updated_by_id' => $updatedBy?->id,
            'is_visible_to_customer' => $isVisibleToCustomer,
        ]);
    }

    /**
     * Get timeline entries for an order
     */
    public static function getOrderTimeline($orderId, $customerView = false)
    {
        $query = static::where('order_id', $orderId)
            ->with(['orderStatus', 'updatedBy'])
            ->orderBy('status_date', 'desc');

        if ($customerView) {
            $query->where('is_visible_to_customer', true);
        }

        return $query->get();
    }
}