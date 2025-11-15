<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Discount extends Model
{
    protected $fillable = [
        'uuid', 'name', 'description', 'type', 'value',
        'min_purchase_amount', 'max_discount_amount',
        'start_date', 'end_date', 'usage_limit', 'usage_count', 'is_active'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
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

    /**
     * Get the products that have this discount
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_product')
            ->withTimestamps();
    }

    /**
     * Check if discount is currently valid
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

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
