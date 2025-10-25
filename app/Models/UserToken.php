<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    protected $fillable = [
        'user_id', 'device_type', 'device_token', 'web_access_token', 'app_access_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
