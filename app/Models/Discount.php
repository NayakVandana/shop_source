<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Discount extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'value', 'minimum_amount', 'usage_limit',
        'used_count', 'starts_at', 'expires_at', 'is_active', 'uuid',
        'description', 'max_discount_amount', 'applicable_products', 'applicable_categories',
        'user_limit', 'first_time_only', 'stackable'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'first_time_only' => 'boolean',
        'stackable' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($discount) {
            if (empty($discount->uuid)) {
                $discount->uuid = Str::uuid();
            }
        });
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        $now = Carbon::now();
        
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount, $userId = null, $products = []): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return 0;
        }

        // Check if coupon applies to specific products
        if (!empty($this->applicable_products) && !empty($products)) {
            $applicableProductIds = collect($products)->pluck('id')->toArray();
            if (!array_intersect($this->applicable_products, $applicableProductIds)) {
                return 0;
            }
        }

        // Check if coupon applies to specific categories
        if (!empty($this->applicable_categories) && !empty($products)) {
            $productCategories = collect($products)->pluck('category_id')->unique()->toArray();
            if (!array_intersect($this->applicable_categories, $productCategories)) {
                return 0;
            }
        }

        // Check first time only restriction
        if ($this->first_time_only && $userId) {
            $userOrderCount = Order::where('user_id', $userId)->count();
            if ($userOrderCount > 0) {
                return 0;
            }
        }

        // Check user usage limit
        if ($this->user_limit && $userId) {
            $userUsageCount = Order::where('user_id', $userId)
                ->where('discount_code', $this->code)
                ->count();
            if ($userUsageCount >= $this->user_limit) {
                return 0;
            }
        }

        $discountAmount = 0;

        if ($this->type === 'percentage') {
            $discountAmount = ($amount * $this->value) / 100;
        } else {
            $discountAmount = $this->value;
        }

        // Apply maximum discount limit
        if ($this->max_discount_amount && $discountAmount > $this->max_discount_amount) {
            $discountAmount = $this->max_discount_amount;
        }

        // Don't exceed the order amount
        return min($discountAmount, $amount);
    }

    public function canStackWith($otherDiscount): bool
    {
        if (!$this->stackable || !$otherDiscount->stackable) {
            return false;
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function getRemainingUses(): int
    {
        if (!$this->usage_limit) {
            return -1; // Unlimited
        }

        return max(0, $this->usage_limit - $this->used_count);
    }

    public function getUsagePercentage(): float
    {
        if (!$this->usage_limit) {
            return 0;
        }

        return ($this->used_count / $this->usage_limit) * 100;
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}