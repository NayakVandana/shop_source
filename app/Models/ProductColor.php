<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductColor extends Model
{
    protected $fillable = [
        'product_id', 'color', 'color_code', 'stock_quantity', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships only - no business logic
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
