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
        'processed_by_type', 'processed_by_id', 'uuid', 'replacement_product_id',
        'replacement_order_id', 'return_shipping_cost', 'refund_method',
        'refund_reference', 'return_window_days', 'is_defective', 'condition_notes'
    ];

    protected $casts = [
        'images' => 'array',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'quantity' => 'integer',
        'return_shipping_cost' => 'decimal:2',
        'return_window_days' => 'integer',
        'is_defective' => 'boolean',
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

    public function replacementProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'replacement_product_id');
    }

    public function replacementOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'replacement_order_id');
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
            'late_delivery' => 'Late Delivery',
            'missing_parts' => 'Missing Parts/Accessories',
            'other' => 'Other',
        ];
    }

    /**
     * Get return types
     */
    public static function getReturnTypes()
    {
        return [
            'return' => 'Return for Refund',
            'exchange' => 'Exchange Product',
            'refund' => 'Refund Only',
            'replacement' => 'Replacement Product',
            'store_credit' => 'Store Credit',
        ];
    }

    /**
     * Get refund methods
     */
    public static function getRefundMethods()
    {
        return [
            'original_payment' => 'Refund to Original Payment Method',
            'store_credit' => 'Store Credit',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'wallet' => 'Digital Wallet',
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
     * Check if return is within return window
     */
    public function isWithinReturnWindow()
    {
        $returnWindow = $this->return_window_days ?? 30;
        return $this->requested_at->diffInDays(now()) <= $returnWindow;
    }

    /**
     * Check if return can be cancelled
     */
    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'approved']) && 
               $this->requested_at->diffInDays(now()) <= 7;
    }

    /**
     * Check if return requires shipping
     */
    public function requiresShipping()
    {
        return in_array($this->type, ['return', 'exchange', 'replacement']) && 
               !in_array($this->reason, ['changed_mind', 'duplicate_order']);
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefundAmount()
    {
        $baseAmount = $this->orderItem->unit_price * $this->quantity;
        
        // Deduct return shipping cost if applicable
        $shippingCost = $this->return_shipping_cost ?? 0;
        
        // For defective products, full refund including shipping
        if ($this->is_defective) {
            return $baseAmount;
        }
        
        // For other reasons, deduct shipping cost
        return max(0, $baseAmount - $shippingCost);
    }

    /**
     * Process return
     */
    public function process($status, $adminNotes = null, $processedBy = null)
    {
        $this->update([
            'status' => $status,
            'admin_notes' => $adminNotes,
            'processed_at' => now(),
            'processed_by_type' => $processedBy ? get_class($processedBy) : null,
            'processed_by_id' => $processedBy?->id,
        ]);

        if ($status === 'completed') {
            $this->update(['completed_at' => now()]);
            $this->handleReturnCompletion();
        }

        return $this;
    }

    /**
     * Handle return completion
     */
    protected function handleReturnCompletion()
    {
        // Restore stock for returned items
        if ($this->orderItem->product->manage_stock) {
            $this->orderItem->product->increment('stock_quantity', $this->quantity);
            
            // Update in_stock status
            if ($this->orderItem->product->stock_quantity > 0) {
                $this->orderItem->product->update(['in_stock' => true]);
            }
        }

        // Handle different return types
        switch ($this->type) {
            case 'refund':
                $this->processRefund();
                break;
            case 'exchange':
            case 'replacement':
                $this->processReplacement();
                break;
            case 'store_credit':
                $this->processStoreCredit();
                break;
        }
    }

    /**
     * Process refund
     */
    protected function processRefund()
    {
        // This would integrate with payment gateway
        // For now, just log the refund
        \Log::info("Refund processed for return {$this->uuid}: {$this->refund_amount}");
    }

    /**
     * Process replacement
     */
    protected function processReplacement()
    {
        if ($this->replacement_product_id) {
            // Create replacement order
            $replacementOrder = $this->createReplacementOrder();
            $this->update(['replacement_order_id' => $replacementOrder->id]);
        }
    }

    /**
     * Process store credit
     */
    protected function processStoreCredit()
    {
        // This would integrate with user wallet/credit system
        \Log::info("Store credit issued for return {$this->uuid}: {$this->refund_amount}");
    }

    /**
     * Create replacement order
     */
    protected function createReplacementOrder()
    {
        $replacementOrder = Order::create([
            'order_number' => 'REP-' . time() . '-' . rand(1000, 9999),
            'user_id' => $this->order->user_id,
            'subtotal' => $this->orderItem->unit_price * $this->quantity,
            'total_amount' => $this->orderItem->unit_price * $this->quantity,
            'status' => 'pending',
            'payment_status' => 'paid', // Replacement is free
            'payment_method' => 'replacement',
            'shipping_address' => $this->order->shipping_address,
            'billing_address' => $this->order->billing_address,
            'notes' => "Replacement order for return {$this->uuid}",
            'is_replacement' => true,
            'original_return_id' => $this->id,
        ]);

        // Create replacement order item
        OrderItem::create([
            'order_id' => $replacementOrder->id,
            'product_id' => $this->replacement_product_id ?? $this->orderItem->product_id,
            'quantity' => $this->quantity,
            'unit_price' => $this->orderItem->unit_price,
            'total_price' => $this->orderItem->unit_price * $this->quantity,
        ]);

        return $replacementOrder;
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

    /**
     * Scope for pending returns
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved returns
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for completed returns
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for returns by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for defective returns
     */
    public function scopeDefective($query)
    {
        return $query->where('is_defective', true);
    }

    /**
     * Scope for returns within window
     */
    public function scopeWithinReturnWindow($query, $days = 30)
    {
        return $query->where('requested_at', '>=', now()->subDays($days));
    }
}