<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'price', 'sale_price',
        'sku', 'stock_quantity', 'manage_stock', 'in_stock', 'weight', 'dimensions',
        'images', 'videos', 'is_featured', 'is_active', 'category_id', 'uuid'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'images' => 'array',
        'videos' => 'array',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($product) {
            if (empty($product->uuid)) {
                $product->uuid = Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function deliveryLocations(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryLocation::class, 'product_delivery_locations')
            ->withPivot([
                'delivery_fee', 'estimated_delivery_days', 'is_available',
                'is_cancelled', 'cancellation_reason', 'cancelled_at',
                'cancelled_by_type', 'cancelled_by_id', 'cancellation_notes'
            ])
            ->withTimestamps();
    }

    public function deliveryIssues(): HasMany
    {
        return $this->hasMany(DeliveryIssue::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    public function featuredReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_featured', true);
    }

    public function getCurrentPriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    public function getDiscountPercentageAttribute(): float
    {
        if (!$this->sale_price || $this->sale_price >= $this->price) {
            return 0;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100, 2);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get the image URLs for the product
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
     * Get the primary image URL
     */
    protected function primaryImageUrl(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->images || !is_array($this->images) || empty($this->images)) {
                    return $this->getDefaultImageUrl();
                }

                return $this->getImageUrl($this->images[0]);
            }
        );
    }

    /**
     * Get the full URL for an image path
     */
    public function getImageUrl($imagePath)
    {
        if (!$imagePath) {
            return $this->getDefaultImageUrl();
        }

        // If it's already a full URL, return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Generate URL for local storage
        return Storage::disk('public')->url($imagePath);
    }

    /**
     * Get the default image URL when no image is available
     */
    public function getDefaultImageUrl()
    {
        return asset('images/no-image.svg');
    }

    /**
     * Store uploaded images and return the paths
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
                
                // Store in public storage under products directory
                $path = $image->storeAs("products/{$productSlug}", $filename, 'public');
                $storedPaths[] = $path;
            }
        }

        return $storedPaths;
    }

    /**
     * Delete product images from storage
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
     * Get image paths for storage (without URLs)
     */
    protected function imagePaths(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->images ?? [];
            }
        );
    }

    /**
     * Get the video URLs for the product
     */
    protected function videoUrls(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->videos || !is_array($this->videos)) {
                    return [];
                }

                return collect($this->videos)->map(function ($videoPath) {
                    return $this->getVideoUrl($videoPath);
                })->toArray();
            }
        );
    }

    /**
     * Get the primary video URL
     */
    protected function primaryVideoUrl(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->videos || !is_array($this->videos) || empty($this->videos)) {
                    return $this->getDefaultVideoUrl();
                }

                return $this->getVideoUrl($this->videos[0]);
            }
        );
    }

    /**
     * Get the full URL for a video path
     */
    public function getVideoUrl($videoPath)
    {
        if (!$videoPath) {
            return $this->getDefaultVideoUrl();
        }

        // If it's already a full URL, return as is
        if (filter_var($videoPath, FILTER_VALIDATE_URL)) {
            return $videoPath;
        }

        // Generate URL for local storage
        return Storage::disk('public')->url($videoPath);
    }

    /**
     * Get the default video URL when no video is available
     */
    public function getDefaultVideoUrl()
    {
        return asset('images/no-video.svg');
    }

    /**
     * Store uploaded videos and return the paths
     */
    public static function storeVideos($uploadedVideos, $productSlug = null)
    {
        if (!$uploadedVideos) {
            return [];
        }

        $storedPaths = [];
        $productSlug = $productSlug ?: 'products';

        foreach ($uploadedVideos as $video) {
            if ($video && $video->isValid()) {
                // Generate unique filename
                $extension = $video->getClientOriginalExtension();
                $filename = time() . '_' . Str::random(10) . '.' . $extension;
                
                // Store in public storage under products directory
                $path = $video->storeAs("products/{$productSlug}/videos", $filename, 'public');
                $storedPaths[] = $path;
            }
        }

        return $storedPaths;
    }

    /**
     * Delete product videos from storage
     */
    public function deleteVideos()
    {
        if ($this->videos && is_array($this->videos)) {
            foreach ($this->videos as $videoPath) {
                if ($videoPath && Storage::disk('public')->exists($videoPath)) {
                    Storage::disk('public')->delete($videoPath);
                }
            }
        }
    }

    /**
     * Get video paths for storage (without URLs)
     */
    protected function videoPaths(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->videos ?? [];
            }
        );
    }

    /**
     * Get the average rating for the product
     */
    protected function averageRating(): Attribute
    {
        return new Attribute(
            get: function () {
                $avgRating = $this->approvedReviews()->avg('rating');
                return $avgRating ? round($avgRating, 1) : 0;
            }
        );
    }

    /**
     * Get the total number of reviews
     */
    protected function totalReviews(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->approvedReviews()->count();
            }
        );
    }

    /**
     * Get the rating distribution
     */
    protected function ratingDistribution(): Attribute
    {
        return new Attribute(
            get: function () {
                $distribution = [];
                $totalReviews = $this->total_reviews;
                
                if ($totalReviews === 0) {
                    return [
                        5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
                    ];
                }

                for ($i = 5; $i >= 1; $i--) {
                    $count = $this->approvedReviews()->where('rating', $i)->count();
                    $distribution[$i] = [
                        'count' => $count,
                        'percentage' => round(($count / $totalReviews) * 100, 1)
                    ];
                }

                return $distribution;
            }
        );
    }

    /**
     * Get recent reviews for the product
     */
    public function getRecentReviews($limit = 5)
    {
        return $this->approvedReviews()
            ->with(['user:id,name'])
            ->orderBy('reviewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured reviews for the product
     */
    public function getFeaturedReviews($limit = 3)
    {
        return $this->featuredReviews()
            ->with(['user:id,name'])
            ->orderBy('reviewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get reviews by rating
     */
    public function getReviewsByRating($rating, $limit = 10)
    {
        return $this->approvedReviews()
            ->where('rating', $rating)
            ->with(['user:id,name'])
            ->orderBy('reviewed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if user has reviewed this product
     */
    public function hasUserReviewed($userId): bool
    {
        return $this->reviews()->where('user_id', $userId)->exists();
    }

    /**
     * Get user's review for this product
     */
    public function getUserReview($userId)
    {
        return $this->reviews()->where('user_id', $userId)->first();
    }

    /**
     * Check if user can review this product
     */
    public function canUserReview($userId): bool
    {
        // User must have purchased this product and not already reviewed it
        $hasPurchased = $this->orderItems()
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->whereIn('status', ['delivered', 'completed']);
            })
            ->exists();

        $hasReviewed = $this->hasUserReviewed($userId);

        return $hasPurchased && !$hasReviewed;
    }

    /**
     * Check if product is deliverable to a specific location
     */
    public function isDeliverableTo($latitude, $longitude)
    {
        $availableLocations = $this->deliveryLocations()
            ->where('is_active', true)
            ->where('is_available', true)
            ->get();

        foreach ($availableLocations as $location) {
            if ($location->isWithinDeliveryRadius($latitude, $longitude)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get nearest delivery location for this product
     */
    public function getNearestDeliveryLocation($latitude, $longitude)
    {
        $availableLocations = $this->deliveryLocations()
            ->where('is_active', true)
            ->where('is_available', true)
            ->get();

        $nearestLocation = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($availableLocations as $location) {
            $distance = $location->calculateDistance($latitude, $longitude);
            if ($distance <= $location->delivery_radius_km && $distance < $minDistance) {
                $minDistance = $distance;
                $nearestLocation = $location;
            }
        }

        return $nearestLocation;
    }

    /**
     * Get delivery info for a specific location
     */
    public function getDeliveryInfo($latitude, $longitude)
    {
        $nearestLocation = $this->getNearestDeliveryLocation($latitude, $longitude);
        
        if (!$nearestLocation) {
            return null;
        }

        $pivot = $this->deliveryLocations()
            ->where('delivery_location_id', $nearestLocation->id)
            ->first()
            ->pivot;

        return [
            'location' => $nearestLocation,
            'delivery_fee' => $pivot->delivery_fee ?? $nearestLocation->delivery_fee,
            'estimated_delivery_days' => $pivot->estimated_delivery_days ?? $nearestLocation->estimated_delivery_days,
            'distance_km' => $nearestLocation->calculateDistance($latitude, $longitude),
            'is_deliverable' => true
        ];
    }

    /**
     * Scope to filter products deliverable to a location
     */
    public function scopeDeliverableTo($query, $latitude, $longitude)
    {
        return $query->whereHas('deliveryLocations', function ($q) use ($latitude, $longitude) {
            $q->where('is_active', true)
              ->where('is_available', true)
              ->where('is_cancelled', false)
              ->whereRaw('ST_Distance_Sphere(
                  POINT(longitude, latitude), 
                  POINT(?, ?)
              ) <= delivery_radius_km * 1000', [$longitude, $latitude]);
        });
    }

    /**
     * Cancel delivery for a specific location
     */
    public function cancelDeliveryToLocation($locationId, $reason, $notes = null, $cancelledBy = null)
    {
        $pivot = $this->deliveryLocations()
            ->where('delivery_location_id', $locationId)
            ->first()
            ->pivot;

        $pivot->update([
            'is_cancelled' => true,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
            'cancelled_by_type' => $cancelledBy ? get_class($cancelledBy) : 'system',
            'cancelled_by_id' => $cancelledBy?->id,
            'cancellation_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Restore delivery for a specific location
     */
    public function restoreDeliveryToLocation($locationId, $restoredBy = null)
    {
        $pivot = $this->deliveryLocations()
            ->where('delivery_location_id', $locationId)
            ->first()
            ->pivot;

        $pivot->update([
            'is_cancelled' => false,
            'cancellation_reason' => null,
            'cancelled_at' => null,
            'cancelled_by_type' => null,
            'cancelled_by_id' => null,
            'cancellation_notes' => null,
        ]);

        return $this;
    }

    /**
     * Check if delivery is cancelled for a location
     */
    public function isDeliveryCancelledToLocation($locationId)
    {
        $pivot = $this->deliveryLocations()
            ->where('delivery_location_id', $locationId)
            ->first()
            ->pivot;

        return $pivot ? $pivot->is_cancelled : false;
    }

    /**
     * Get cancelled delivery locations
     */
    public function getCancelledDeliveryLocations()
    {
        return $this->deliveryLocations()
            ->wherePivot('is_cancelled', true)
            ->get();
    }

    /**
     * Get active delivery locations (not cancelled)
     */
    public function getActiveDeliveryLocations()
    {
        return $this->deliveryLocations()
            ->wherePivot('is_cancelled', false)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Report delivery issue
     */
    public function reportDeliveryIssue($orderId, $locationId, $issueType, $title, $description, $reportedBy = null, $metadata = null)
    {
        return DeliveryIssue::create([
            'order_id' => $orderId,
            'product_id' => $this->id,
            'delivery_location_id' => $locationId,
            'issue_type' => $issueType,
            'title' => $title,
            'description' => $description,
            'reported_by_type' => $reportedBy ? get_class($reportedBy) : 'system',
            'reported_by_id' => $reportedBy?->id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get delivery issues for this product
     */
    public function getDeliveryIssues($status = null)
    {
        $query = $this->deliveryIssues()->with(['order', 'deliveryLocation']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('reported_at', 'desc')->get();
    }
}