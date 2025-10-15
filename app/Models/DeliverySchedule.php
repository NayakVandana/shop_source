<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeliverySchedule extends Model
{
    protected $fillable = [
        'product_id', 'delivery_location_id', 'delivery_date', 'start_time', 'end_time',
        'max_orders', 'booked_orders', 'delivery_fee', 'is_available', 'is_express',
        'delivery_type', 'notes', 'time_slots', 'cutoff_time', 'uuid'
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'delivery_fee' => 'decimal:2',
        'is_available' => 'boolean',
        'is_express' => 'boolean',
        'time_slots' => 'array',
        'cutoff_time' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($schedule) {
            if (empty($schedule->uuid)) {
                $schedule->uuid = Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get delivery types
     */
    public static function getDeliveryTypes()
    {
        return [
            'standard' => 'Standard Delivery',
            'express' => 'Express Delivery',
            'scheduled' => 'Scheduled Delivery',
            'same_day' => 'Same Day Delivery',
            'next_day' => 'Next Day Delivery',
        ];
    }

    /**
     * Check if schedule is available for booking
     */
    public function isAvailableForBooking()
    {
        if (!$this->is_available) {
            return false;
        }

        if ($this->max_orders && $this->booked_orders >= $this->max_orders) {
            return false;
        }

        if ($this->cutoff_time && now()->isAfter($this->cutoff_time)) {
            return false;
        }

        return true;
    }

    /**
     * Check if it's same day delivery
     */
    public function isSameDayDelivery()
    {
        return $this->delivery_date->isToday();
    }

    /**
     * Check if it's next day delivery
     */
    public function isNextDayDelivery()
    {
        return $this->delivery_date->isTomorrow();
    }

    /**
     * Get available time slots
     */
    public function getAvailableTimeSlots()
    {
        if (!$this->time_slots) {
            return [];
        }

        $availableSlots = [];
        foreach ($this->time_slots as $slot) {
            if ($this->isTimeSlotAvailable($slot)) {
                $availableSlots[] = $slot;
            }
        }

        return $availableSlots;
    }

    /**
     * Check if time slot is available
     */
    public function isTimeSlotAvailable($slot)
    {
        // Check if slot is within delivery time range
        if ($this->start_time && $this->end_time) {
            $slotTime = Carbon::parse($slot['time']);
            if ($slotTime->lt($this->start_time) || $slotTime->gt($this->end_time)) {
                return false;
            }
        }

        // Check if slot has capacity
        if (isset($slot['max_orders']) && isset($slot['booked_orders'])) {
            return $slot['booked_orders'] < $slot['max_orders'];
        }

        return true;
    }

    /**
     * Book a time slot
     */
    public function bookTimeSlot($slotTime)
    {
        if (!$this->isAvailableForBooking()) {
            throw new \Exception('Schedule is not available for booking');
        }

        $this->increment('booked_orders');

        // Update time slot booking if applicable
        if ($this->time_slots) {
            $timeSlots = $this->time_slots;
            foreach ($timeSlots as &$slot) {
                if ($slot['time'] === $slotTime) {
                    $slot['booked_orders'] = ($slot['booked_orders'] ?? 0) + 1;
                    break;
                }
            }
            $this->update(['time_slots' => $timeSlots]);
        }

        return $this;
    }

    /**
     * Release a time slot
     */
    public function releaseTimeSlot($slotTime)
    {
        $this->decrement('booked_orders');

        // Update time slot booking if applicable
        if ($this->time_slots) {
            $timeSlots = $this->time_slots;
            foreach ($timeSlots as &$slot) {
                if ($slot['time'] === $slotTime) {
                    $slot['booked_orders'] = max(0, ($slot['booked_orders'] ?? 1) - 1);
                    break;
                }
            }
            $this->update(['time_slots' => $timeSlots]);
        }

        return $this;
    }

    /**
     * Get delivery date display
     */
    public function getDeliveryDateDisplayAttribute()
    {
        if ($this->isSameDayDelivery()) {
            return 'Today';
        } elseif ($this->isNextDayDelivery()) {
            return 'Tomorrow';
        } else {
            return $this->delivery_date->format('M d, Y');
        }
    }

    /**
     * Get delivery time display
     */
    public function getDeliveryTimeDisplayAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
        } elseif ($this->start_time) {
            return 'From ' . $this->start_time->format('H:i');
        } elseif ($this->end_time) {
            return 'Until ' . $this->end_time->format('H:i');
        }
        
        return 'All day';
    }

    /**
     * Scope for today's deliveries
     */
    public function scopeToday($query)
    {
        return $query->whereDate('delivery_date', today());
    }

    /**
     * Scope for tomorrow's deliveries
     */
    public function scopeTomorrow($query)
    {
        return $query->whereDate('delivery_date', tomorrow());
    }

    /**
     * Scope for available schedules
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                    ->where(function($q) {
                        $q->whereNull('max_orders')
                          ->orWhereRaw('booked_orders < max_orders');
                    })
                    ->where(function($q) {
                        $q->whereNull('cutoff_time')
                          ->orWhere('cutoff_time', '>', now());
                    });
    }

    /**
     * Scope for express delivery
     */
    public function scopeExpress($query)
    {
        return $query->where('is_express', true);
    }

    /**
     * Scope for specific delivery type
     */
    public function scopeDeliveryType($query, $type)
    {
        return $query->where('delivery_type', $type);
    }
}