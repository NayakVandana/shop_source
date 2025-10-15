<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class DeliveryIssue extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'delivery_location_id', 'issue_type', 'title',
        'description', 'status', 'resolution', 'resolution_notes', 'reported_at',
        'resolved_at', 'reported_by_type', 'reported_by_id', 'resolved_by_type',
        'resolved_by_id', 'metadata', 'uuid'
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($issue) {
            if (empty($issue->uuid)) {
                $issue->uuid = Str::uuid();
            }
            if (empty($issue->reported_at)) {
                $issue->reported_at = now();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class);
    }

    public function reportedBy(): MorphTo
    {
        return $this->morphTo('reported_by');
    }

    public function resolvedBy(): MorphTo
    {
        return $this->morphTo('resolved_by');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get issue types
     */
    public static function getIssueTypes()
    {
        return [
            'product_unavailable' => 'Product Unavailable',
            'delivery_location_issue' => 'Delivery Location Issue',
            'logistics_problem' => 'Logistics Problem',
            'weather_issue' => 'Weather Issue',
            'address_issue' => 'Address Issue',
            'customer_unavailable' => 'Customer Unavailable',
            'other' => 'Other',
        ];
    }

    /**
     * Get resolution types
     */
    public static function getResolutionTypes()
    {
        return [
            'delivery_cancelled' => 'Delivery Cancelled',
            'delivery_delayed' => 'Delivery Delayed',
            'delivery_rerouted' => 'Delivery Rerouted',
            'product_replaced' => 'Product Replaced',
            'refund_issued' => 'Refund Issued',
            'other' => 'Other',
        ];
    }

    /**
     * Check if issue is resolved
     */
    public function isResolved()
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if issue is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if delivery was cancelled due to this issue
     */
    public function isDeliveryCancelled()
    {
        return $this->resolution === 'delivery_cancelled';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'reported' => '#F59E0B',
            'investigating' => '#3B82F6',
            'resolved' => '#10B981',
            'cancelled' => '#EF4444',
            default => '#6B7280',
        };
    }

    /**
     * Resolve issue
     */
    public function resolve($resolution, $resolutionNotes, $resolvedBy = null)
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
            'resolved_by_type' => $resolvedBy ? get_class($resolvedBy) : null,
            'resolved_by_id' => $resolvedBy?->id,
        ]);

        return $this;
    }

    /**
     * Cancel issue
     */
    public function cancel($reason, $cancelledBy = null)
    {
        $this->update([
            'status' => 'cancelled',
            'resolution' => 'other',
            'resolution_notes' => $reason,
            'resolved_at' => now(),
            'resolved_by_type' => $cancelledBy ? get_class($cancelledBy) : null,
            'resolved_by_id' => $cancelledBy?->id,
        ]);

        return $this;
    }
}