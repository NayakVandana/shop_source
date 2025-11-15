<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CouponCode extends Model
{
    protected $fillable = [
        'uuid', 'code', 'name', 'description', 'type', 'value',
        'min_purchase_amount', 'max_discount_amount',
        'start_date', 'end_date', 'usage_limit', 'usage_limit_per_user',
        'usage_count', 'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($coupon) {
            if (empty($coupon->uuid)) {
                $coupon->uuid = Str::uuid();
            }
            if (empty($coupon->code)) {
                $coupon->code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Check if coupon code is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon can be used by a specific user
     */
    public function canBeUsedByUser($userId = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // If per-user limit is set, check usage (would need a usage tracking table)
        // For now, we'll just check the general validity
        return true;
    }

    /**
     * Calculate discount amount for a given price
     */
    public function calculateDiscount($price): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_purchase_amount && $price < $this->min_purchase_amount) {
            return 0;
        }

        $discount = 0;
        
        if ($this->type === 'percentage') {
            $discount = ($price * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // Apply max discount limit if set
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        // Don't exceed the price
        return min($discount, $price);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
