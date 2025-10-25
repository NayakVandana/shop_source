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
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(Str::random(8));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
}
