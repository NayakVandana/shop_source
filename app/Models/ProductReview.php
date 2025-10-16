<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_id', 'rating', 'title', 'comment',
        'images', 'is_verified_purchase', 'is_approved', 'is_featured',
        'helpful_count', 'not_helpful_count', 'metadata', 'reviewed_at', 'uuid'
    ];

    protected $casts = [
        'rating' => 'integer',
        'images' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($review) {
            if (empty($review->uuid)) {
                $review->uuid = Str::uuid();
            }
            if (empty($review->reviewed_at)) {
                $review->reviewed_at = now();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    /**
     * Get the review image URLs
     */
    protected function imageUrls(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->images || !is_array($this->images)) {
                    return [];
                }

                return collect($this->images)->map(function ($imagePath) {
                    return $this->getImageUrl($imagePath);
                })->toArray();
            }
        );
    }

    /**
     * Get the full URL for a review image path
     */
    public function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // If it's already a full URL, return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Generate URL for local storage
        return Storage::disk('public')->url($imagePath);
    }

    /**
     * Store uploaded review images and return the paths
     */
    public static function storeImages($uploadedImages, $productSlug = null)
    {
        if (!$uploadedImages) {
            return [];
        }

        $storedPaths = [];
        $productSlug = $productSlug ?: 'products';

        foreach ($uploadedImages as $image) {
            if ($image && $image->isValid()) {
                // Generate unique filename
                $extension = $image->getClientOriginalExtension();
                $filename = time() . '_' . Str::random(10) . '.' . $extension;
                
                // Store in public storage under reviews directory
                $path = $image->storeAs("reviews/{$productSlug}", $filename, 'public');
                $storedPaths[] = $path;
            }
        }

        return $storedPaths;
    }

    /**
     * Delete review images from storage
     */
    public function deleteImages()
    {
        if ($this->images && is_array($this->images)) {
            foreach ($this->images as $imagePath) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
        }
    }

    /**
     * Get the helpful percentage
     */
    public function getHelpfulPercentageAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Check if user can edit this review
     */
    public function canEdit($userId): bool
    {
        return $this->user_id === $userId && $this->created_at->diffInDays(now()) <= 30;
    }

    /**
     * Check if user can delete this review
     */
    public function canDelete($userId): bool
    {
        return $this->user_id === $userId && $this->created_at->diffInDays(now()) <= 7;
    }

    /**
     * Scope for approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for featured reviews
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for verified purchases
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope for rating range
     */
    public function scopeRatingBetween($query, $min, $max)
    {
        return $query->whereBetween('rating', [$min, $max]);
    }

    /**
     * Scope for recent reviews
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('reviewed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for most helpful reviews
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderByRaw('(helpful_count - not_helpful_count) DESC');
    }

    /**
     * Scope for highest rated reviews
     */
    public function scopeHighestRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope for lowest rated reviews
     */
    public function scopeLowestRated($query)
    {
        return $query->orderBy('rating', 'asc');
    }
}