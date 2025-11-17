<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use App\Helpers\MediaStorageService;

class ProductMedia extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'disk',
        'url',
        'sort_order',
        'is_primary',
        'color',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns this media
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the media file
     * Accessor for the url attribute
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // If URL is already stored, return it
                if ($value) {
                    return $value;
                }

                // Generate URL based on disk type
                if ($this->disk === 's3') {
                    return Storage::disk('s3')->url($this->file_path);
                }

                // For public disk
                return Storage::disk('public')->url($this->file_path);
            }
        );
    }

    /**
     * Delete the file from storage when media is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($media) {
            // Delete file from storage using MediaStorageService
            if ($media->file_path) {
                MediaStorageService::deleteFile($media->file_path, $media->disk);
            }
        });
    }

    /**
     * Scope to get only images
     */
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    /**
     * Scope to get only videos
     */
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    /**
     * Scope to get primary media
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
