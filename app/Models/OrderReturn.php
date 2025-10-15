<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class OrderReturn extends Model
{
    protected $fillable = [
        'order_id', 'order_item_id', 'type', 'status', 'reason', 'description',
        'quantity', 'refund_amount', 'return_tracking_number', 'admin_notes',
        'customer_notes', 'images', 'requested_at', 'processed_at', 'completed_at',
        'processed_by_type', 'processed_by_id', 'uuid'
    ];

    protected $casts = [
        'images' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'quantity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($return) {
            if (empty($return->uuid)) {
                $return->uuid = Str::uuid();
            }
            if (empty($return->requested_at)) {
                $return->requested_at = now();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function processedBy(): MorphTo
    {
        return $this->morphTo('processed_by');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get return reasons
     */
    public static function getReturnReasons()
    {
        return [
            'defective_product' => 'Defective Product',
            'wrong_item' => 'Wrong Item Received',
            'size_issue' => 'Size/Color Issue',
            'quality_issue' => 'Quality Issue',
            'damaged_during_shipping' => 'Damaged During Shipping',
            'not_as_described' => 'Not As Described',
            'changed_mind' => 'Changed Mind',
            'duplicate_order' => 'Duplicate Order',
            'other' => 'Other',
        ];
    }

    /**
     * Check if return is processable
     */
    public function isProcessable()
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    /**
     * Check if return is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if return is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => '#F59E0B',
            'approved' => '#10B981',
            'rejected' => '#EF4444',
            'processing' => '#3B82F6',
            'completed' => '#059669',
            'cancelled' => '#6B7280',
            default => '#6B7280',
        };
    }
}