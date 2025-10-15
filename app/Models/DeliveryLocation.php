<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class DeliveryLocation extends Model
{
    protected $fillable = [
        'name', 'city', 'state', 'country', 'postal_code',
        'latitude', 'longitude', 'address', 'is_active',
        'delivery_radius_km', 'delivery_fee', 'estimated_delivery_days', 'uuid'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'delivery_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'delivery_radius_km' => 'integer',
        'estimated_delivery_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($location) {
            if (empty($location->uuid)) {
                $location->uuid = Str::uuid();
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_delivery_locations')
            ->withPivot(['delivery_fee', 'estimated_delivery_days', 'is_available'])
            ->withTimestamps();
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public function calculateDistance($latitude, $longitude)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDiff = deg2rad($latitude - $this->latitude);
        $lonDiff = deg2rad($longitude - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a location is within delivery radius
     */
    public function isWithinDeliveryRadius($latitude, $longitude)
    {
        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $this->delivery_radius_km;
    }

    /**
     * Get formatted address
     */
    protected function fullAddress(): Attribute
    {
        return new Attribute(
            get: function () {
                $parts = array_filter([
                    $this->address,
                    $this->city,
                    $this->state,
                    $this->postal_code,
                    $this->country
                ]);
                
                return implode(', ', $parts);
            }
        );
    }

    /**
     * Find nearest delivery location to given coordinates
     */
    public static function findNearest($latitude, $longitude, $radius = null)
    {
        $query = static::where('is_active', true);

        if ($radius) {
            $query->where('delivery_radius_km', '>=', $radius);
        }

        return $query->get()->map(function ($location) use ($latitude, $longitude) {
            $location->distance = $location->calculateDistance($latitude, $longitude);
            return $location;
        })->sortBy('distance')->first();
    }

    /**
     * Get all locations within delivery radius
     */
    public static function getWithinRadius($latitude, $longitude, $radius = null)
    {
        $query = static::where('is_active', true);

        if ($radius) {
            $query->where('delivery_radius_km', '>=', $radius);
        }

        return $query->get()->filter(function ($location) use ($latitude, $longitude) {
            return $location->isWithinDeliveryRadius($latitude, $longitude);
        })->sortBy(function ($location) use ($latitude, $longitude) {
            return $location->calculateDistance($latitude, $longitude);
        });
    }
}