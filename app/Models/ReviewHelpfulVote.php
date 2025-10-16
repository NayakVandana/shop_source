<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReviewHelpfulVote extends Model
{
    protected $fillable = [
        'review_id', 'user_id', 'is_helpful', 'uuid'
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($vote) {
            if (empty($vote->uuid)) {
                $vote->uuid = Str::uuid();
            }
        });
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}