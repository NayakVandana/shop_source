<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Helpers\MediaStorageService;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'short_description', 'price', 'sale_price',
        'sku', 'stock_quantity', 'manage_stock', 'in_stock', 'weight', 'dimensions',
        'is_featured', 'is_active', 'category_id', 'uuid'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'image_urls',
        'primary_image_url',
        'video_urls',
        'primary_video_url',
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

        // Delete associated media when product is deleted
        static::deleting(function ($product) {
            $product->deleteImages();
            $product->deleteVideos();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all media (images and videos) for this product
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    /**
     * Get only images for this product
     */
    public function imagesMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->where('type', 'image')->orderBy('sort_order');
    }

    /**
     * Get only videos for this product
     */
    public function videosMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->where('type', 'video')->orderBy('sort_order');
    }

    /**
     * Get primary image
     */
    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductMedia::class)
            ->where('type', 'image')
            ->where('is_primary', true)
            ->limit(1);
    }

    /**
     * Get primary video
     */
    public function primaryVideo(): HasMany
    {
        return $this->hasMany(ProductMedia::class)
            ->where('type', 'video')
            ->where('is_primary', true)
            ->limit(1);
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
                if ($this->relationLoaded('imagesMedia')) {
                    return $this->imagesMedia->pluck('url')->toArray();
                }
                
                return $this->imagesMedia()->get()->pluck('url')->toArray();
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
                $primaryImage = $this->imagesMedia()->where('is_primary', true)->first();
                if ($primaryImage) {
                    return $primaryImage->url;
                }
                
                // Try first image if no primary set
                $firstImage = $this->imagesMedia()->first();
                if ($firstImage) {
                    return $firstImage->url;
                }
                
                return $this->getDefaultImageUrl();
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
     * Store uploaded images using new media system
     * 
     * @param array|UploadedFile $uploadedImages Single file or array of files
     * @param bool $setFirstAsPrimary Whether to set first image as primary
     * @return array Array of ProductMedia models
     */
    public function storeImages($uploadedImages, $setFirstAsPrimary = true)
    {
        if (!$uploadedImages) {
            return [];
        }

        // Convert single file to array
        if ($uploadedImages instanceof UploadedFile) {
            $uploadedImages = [$uploadedImages];
        }

        $productSlug = $this->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedImages as $index => $image) {
            if ($image && $image->isValid()) {
                // Store file using MediaStorageService
                $mediaData = MediaStorageService::storeFile(
                    $image,
                    'image',
                    'products',
                    $productSlug
                );

                // Create media record
                $mediaRecord = $this->media()->create([
                    'type' => 'image',
                    'file_path' => $mediaData['file_path'],
                    'file_name' => $mediaData['file_name'],
                    'mime_type' => $mediaData['mime_type'],
                    'file_size' => $mediaData['file_size'],
                    'disk' => $mediaData['disk'],
                    'url' => $mediaData['url'],
                    'sort_order' => $index,
                    'is_primary' => $setFirstAsPrimary && $index === 0,
                ]);

                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    /**
     * Store uploaded images and return the paths (legacy method for backward compatibility)
     */
    public static function storeImagesLegacy($uploadedImages, $productSlug = null)
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
        $mediaImages = $this->imagesMedia()->get();
        foreach ($mediaImages as $media) {
            MediaStorageService::deleteFile($media->file_path, $media->disk);
            $media->delete();
        }
    }

    /**
     * Get image paths for storage (without URLs)
     */
    protected function imagePaths(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->imagesMedia()->get()->pluck('file_path')->toArray();
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
                if ($this->relationLoaded('videosMedia')) {
                    return $this->videosMedia->pluck('url')->toArray();
                }
                
                return $this->videosMedia()->get()->pluck('url')->toArray();
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
                $primaryVideo = $this->videosMedia()->where('is_primary', true)->first();
                if ($primaryVideo) {
                    return $primaryVideo->url;
                }
                
                // Try first video if no primary set
                $firstVideo = $this->videosMedia()->first();
                if ($firstVideo) {
                    return $firstVideo->url;
                }
                
                return $this->getDefaultVideoUrl();
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
     * Store uploaded videos using new media system
     * 
     * @param array|UploadedFile $uploadedVideos Single file or array of files
     * @param bool $setFirstAsPrimary Whether to set first video as primary
     * @return array Array of ProductMedia models
     */
    public function storeVideos($uploadedVideos, $setFirstAsPrimary = true)
    {
        if (!$uploadedVideos) {
            return [];
        }

        // Convert single file to array
        if ($uploadedVideos instanceof UploadedFile) {
            $uploadedVideos = [$uploadedVideos];
        }

        $productSlug = $this->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedVideos as $index => $video) {
            if ($video && $video->isValid()) {
                // Store file using MediaStorageService
                $mediaData = MediaStorageService::storeFile(
                    $video,
                    'video',
                    'products',
                    $productSlug
                );

                // Create media record
                $mediaRecord = $this->media()->create([
                    'type' => 'video',
                    'file_path' => $mediaData['file_path'],
                    'file_name' => $mediaData['file_name'],
                    'mime_type' => $mediaData['mime_type'],
                    'file_size' => $mediaData['file_size'],
                    'disk' => $mediaData['disk'],
                    'url' => $mediaData['url'],
                    'sort_order' => $index,
                    'is_primary' => $setFirstAsPrimary && $index === 0,
                ]);

                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    /**
     * Store uploaded videos and return the paths (legacy method for backward compatibility)
     */
    public static function storeVideosLegacy($uploadedVideos, $productSlug = null)
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
        $mediaVideos = $this->videosMedia()->get();
        foreach ($mediaVideos as $media) {
            MediaStorageService::deleteFile($media->file_path, $media->disk);
            $media->delete();
        }
    }

    /**
     * Get video paths for storage (without URLs)
     */
    protected function videoPaths(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->videosMedia()->get()->pluck('file_path')->toArray();
            }
        );
    }
}
