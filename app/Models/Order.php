<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'subtotal', 'discount_amount', 'discount_code',
        'tax_amount', 'shipping_amount', 'total_amount', 'status', 'payment_status',
        'payment_method', 'shipping_address', 'billing_address', 'notes', 'uuid',
        'tracking_number', 'delivery_company', 'shipped_at', 'delivered_at',
        'estimated_delivery_date', 'order_type', 'is_cancellable', 'is_returnable',
        'cancelled_at', 'cancellation_reason', 'delivery_location_id', 'order_status_id',
        'delivery_notes', 'delivery_contact_phone', 'special_instructions',
        'delivery_schedule_id', 'preferred_delivery_date', 'preferred_delivery_time',
        'delivery_type', 'is_express_delivery', 'express_delivery_fee', 'delivery_cutoff_time',
        'delivery_instructions', 'time_slot_preferences'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_cancellable' => 'boolean',
        'is_returnable' => 'boolean',
        'delivery_notes' => 'array',
        'preferred_delivery_date' => 'date',
        'preferred_delivery_time' => 'datetime:H:i',
        'is_express_delivery' => 'boolean',
        'express_delivery_fee' => 'decimal:2',
        'delivery_cutoff_time' => 'datetime',
        'time_slot_preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(OrderTimeline::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            $order->order_number = 'ORD-' . strtoupper(uniqid());
            if (empty($order->uuid)) {
                $order->uuid = Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Update order status and create timeline entry
     */
    public function updateStatus($status, $title, $description = null, $metadata = null, $updatedBy = null, $isVisibleToCustomer = true)
    {
        $this->update(['status' => $status]);
        
        // Update order status reference
        $orderStatus = OrderStatus::where('slug', $status)->first();
        if ($orderStatus) {
            $this->update(['order_status_id' => $orderStatus->id]);
        }

        // Create timeline entry
        OrderTimeline::createEntry(
            $this->id,
            $status,
            $title,
            $description,
            $metadata,
            $updatedBy,
            $isVisibleToCustomer
        );

        return $this;
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled()
    {
        return $this->is_cancellable && 
               in_array($this->status, ['pending', 'confirmed']) && 
               !$this->cancelled_at &&
               $this->created_at->diffInDays(now()) <= 7; // 7 days cancellation window
    }

    /**
     * Check if order can be returned
     */
    public function canBeReturned()
    {
        return $this->is_returnable && 
               in_array($this->status, ['delivered', 'completed']) && 
               $this->delivered_at && 
               $this->delivered_at->diffInDays(now()) <= 30; // 30 days return window
    }

    /**
     * Check if order can be partially returned
     */
    public function canBePartiallyReturned()
    {
        return $this->canBeReturned() && 
               $this->returns()->where('status', '!=', 'rejected')->count() < $this->orderItems()->count();
    }

    /**
     * Check if order item can be returned
     */
    public function canReturnItem($orderItemId)
    {
        $orderItem = $this->orderItems()->find($orderItemId);
        if (!$orderItem) {
            return false;
        }

        // Check if already returned
        $existingReturn = $this->returns()
            ->where('order_item_id', $orderItemId)
            ->whereIn('status', ['pending', 'approved', 'processing', 'completed'])
            ->first();

        if ($existingReturn) {
            return false;
        }

        return $this->canBeReturned();
    }

    /**
     * Get returnable items
     */
    public function getReturnableItems()
    {
        $returnedItemIds = $this->returns()
            ->whereIn('status', ['pending', 'approved', 'processing', 'completed'])
            ->pluck('order_item_id')
            ->toArray();

        return $this->orderItems()
            ->whereNotIn('id', $returnedItemIds)
            ->get();
    }

    /**
     * Calculate total refundable amount
     */
    public function getTotalRefundableAmount()
    {
        $totalRefunded = $this->returns()
            ->where('status', 'completed')
            ->sum('refund_amount');

        return $this->total_amount - $totalRefunded;
    }

    /**
     * Get cancellation reasons
     */
    public static function getCancellationReasons()
    {
        return [
            'changed_mind' => 'Changed Mind',
            'found_better_price' => 'Found Better Price',
            'shipping_too_slow' => 'Shipping Too Slow',
            'product_not_needed' => 'Product Not Needed',
            'duplicate_order' => 'Duplicate Order',
            'payment_issue' => 'Payment Issue',
            'address_error' => 'Address Error',
            'other' => 'Other',
        ];
    }

    /**
     * Cancel order
     */
    public function cancel($reason, $cancelledBy = null)
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Restore stock for all items
        foreach ($this->orderItems as $item) {
            if ($item->product->manage_stock) {
                $item->product->increment('stock_quantity', $item->quantity);
                
                // Update in_stock status
                if ($item->product->stock_quantity > 0) {
                    $item->product->update(['in_stock' => true]);
                }
            }
        }

        $this->updateStatus(
            'cancelled',
            'Order Cancelled',
            "Order cancelled. Reason: {$reason}",
            ['cancellation_reason' => $reason],
            $cancelledBy
        );

        return $this;
    }

    /**
     * Process refund for cancelled order
     */
    public function processCancellationRefund($refundMethod = 'original_payment', $refundReference = null)
    {
        if ($this->status !== 'cancelled') {
            throw new \Exception('Order must be cancelled to process refund');
        }

        // This would integrate with payment gateway
        // For now, just log the refund
        \Log::info("Cancellation refund processed for order {$this->order_number}: {$this->total_amount} via {$refundMethod}");

        $this->update([
            'payment_status' => 'refunded',
            'refund_method' => $refundMethod,
            'refund_reference' => $refundReference,
            'refunded_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark order as shipped
     */
    public function markAsShipped($trackingNumber, $deliveryCompany = null, $updatedBy = null)
    {
        $this->update([
            'status' => 'shipped',
            'tracking_number' => $trackingNumber,
            'delivery_company' => $deliveryCompany,
            'shipped_at' => now(),
        ]);

        $this->updateStatus(
            'shipped',
            'Order Shipped',
            "Order has been shipped. Tracking number: {$trackingNumber}",
            [
                'tracking_number' => $trackingNumber,
                'delivery_company' => $deliveryCompany,
                'shipped_at' => $this->shipped_at
            ],
            $updatedBy
        );

        return $this;
    }

    /**
     * Mark order as delivered
     */
    public function markAsDelivered($updatedBy = null)
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->updateStatus(
            'delivered',
            'Order Delivered',
            'Order has been successfully delivered',
            ['delivered_at' => $this->delivered_at],
            $updatedBy
        );

        return $this;
    }

    /**
     * Get order timeline for customer view
     */
    public function getCustomerTimeline()
    {
        return OrderTimeline::getOrderTimeline($this->id, true);
    }

    /**
     * Get order timeline for admin view
     */
    public function getAdminTimeline()
    {
        return OrderTimeline::getOrderTimeline($this->id, false);
    }

    /**
     * Get delivery status
     */
    public function getDeliveryStatusAttribute()
    {
        if ($this->delivered_at) {
            return 'delivered';
        } elseif ($this->shipped_at) {
            return 'shipped';
        } elseif ($this->status === 'processing') {
            return 'processing';
        } elseif ($this->status === 'confirmed') {
            return 'confirmed';
        } else {
            return 'pending';
        }
    }

    /**
     * Get estimated delivery date
     */
    public function getEstimatedDeliveryDateAttribute()
    {
        if ($this->estimated_delivery_date) {
            return $this->estimated_delivery_date;
        }

        if ($this->shipped_at && $this->deliveryLocation) {
            return $this->shipped_at->addDays($this->deliveryLocation->estimated_delivery_days);
        }

        return null;
    }
}